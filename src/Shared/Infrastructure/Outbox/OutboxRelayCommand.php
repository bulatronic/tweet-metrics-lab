<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Infrastructure\Metrics\OutboxMetrics;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Long-running supervisor process: polls outbox and dispatches events to RabbitMQ via Messenger.
 */
#[AsCommand(
    name: 'app:outbox:relay',
    description: 'Relay unpublished outbox messages to the Messenger async transport (RabbitMQ)',
)]
final class OutboxRelayCommand extends Command
{
    private const int DEFAULT_BATCH_SIZE = 100;
    private const int DEFAULT_IDLE_SLEEP_MS = 500;

    private bool $shouldStop = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OutboxMessageRepository $outboxMessageRepository,
        #[Autowire(service: 'event.bus')]
        private readonly MessageBusInterface $eventBus,
        private readonly SerializerInterface $serializer,
        private readonly OutboxMetrics $outboxMetrics,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Max messages per batch', (string) self::DEFAULT_BATCH_SIZE)
            ->addOption('idle-sleep-ms', null, InputOption::VALUE_REQUIRED, 'Sleep when the outbox is empty (ms)', (string) self::DEFAULT_IDLE_SLEEP_MS)
            ->addOption('once', null, InputOption::VALUE_NONE, 'Process a single batch and exit (useful for tests)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registerSignalHandlers();

        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $idleSleepMs = max(0, (int) $input->getOption('idle-sleep-ms'));
        $once = (bool) $input->getOption('once');

        $output->writeln('<info>Outbox relay started</info>');

        do {
            $processed = $this->processBatch($batchSize);

            if ($this->shouldStop) {
                $output->writeln('<comment>Shutdown signal received, stopping after current batch</comment>');
                break;
            }

            if (0 === $processed && !$once) {
                $this->idleSleep($idleSleepMs);
            }
        } while (!$once && !$this->shouldStop);

        return Command::SUCCESS;
    }

    private function registerSignalHandlers(): void
    {
        if (!\function_exists('pcntl_signal') || !\function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);

        $stop = function (): void {
            $this->shouldStop = true;
        };

        pcntl_signal(\SIGTERM, $stop);
        pcntl_signal(\SIGINT, $stop);
    }

    private function idleSleep(int $idleSleepMs): void
    {
        $remainingMicros = $idleSleepMs * 1000;
        $chunkMicros = 100_000; // 100ms

        while ($remainingMicros > 0 && !$this->shouldStop) {
            $sleep = min($chunkMicros, $remainingMicros);
            usleep($sleep);
            $remainingMicros -= $sleep;
        }
    }

    private function processBatch(int $batchSize): int
    {
        $startedAt = microtime(true);
        $processed = 0;

        try {
            $this->entityManager->wrapInTransaction(function () use ($batchSize, &$processed): void {
                $messages = $this->outboxMessageRepository->findUnpublishedForUpdate($batchSize);

                foreach ($messages as $message) {
                    try {
                        $event = $this->deserialize($message);
                        $this->eventBus->dispatch($event);

                        $publishedAt = new \DateTimeImmutable();
                        $message->markPublished($publishedAt);
                        ++$processed;

                        $this->outboxMetrics->incrementPublished();
                        $this->outboxMetrics->observeMessageLag(
                            (float) $publishedAt->format('U.u') - (float) $message->getCreatedAt()->format('U.u'),
                        );
                    } catch (\Throwable $exception) {
                        $message->incrementAttempts();
                        $this->outboxMetrics->incrementFailed();

                        if ($message->hasReachedMaxAttempts()) {
                            $message->markFailed(new \DateTimeImmutable());
                            $this->outboxMetrics->incrementDead();
                            $this->logger->error('Outbox message moved to dead state after max attempts', [
                                'outbox_message_id' => $message->getId()->toRfc4122(),
                                'attempts' => $message->getAttempts(),
                                'max_attempts' => $message->getMaxAttempts(),
                                'exception' => $exception,
                            ]);
                        } else {
                            $this->logger->error('Failed to relay outbox message', [
                                'outbox_message_id' => $message->getId()->toRfc4122(),
                                'attempts' => $message->getAttempts(),
                                'max_attempts' => $message->getMaxAttempts(),
                                'exception' => $exception,
                            ]);
                        }
                    }
                }

                $this->entityManager->flush();
            });
        } catch (\Throwable $exception) {
            $this->logger->error('Outbox relay batch failed', ['exception' => $exception]);
            $this->entityManager->clear();
        }

        try {
            $this->outboxMetrics->observeRelayBatchDuration(microtime(true) - $startedAt);
            $this->outboxMetrics->setPendingMessages($this->outboxMessageRepository->countPending());
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to update outbox Prometheus metrics', [
                'exception' => $exception,
            ]);
        }

        return $processed;
    }

    private function deserialize(OutboxMessage $message): object
    {
        $payload = $message->getPayload();
        $class = $payload['class'] ?? null;
        $data = $payload['data'] ?? null;

        if (!is_string($class) || !class_exists($class) || !is_array($data)) {
            throw new \RuntimeException(sprintf('Invalid outbox payload for message "%s".', $message->getId()->toRfc4122()));
        }

        $event = $this->serializer->denormalize($data, $class, 'json', [
            DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM,
        ]);

        if (!is_object($event)) {
            throw new \RuntimeException(sprintf('Failed to denormalize outbox event "%s".', $class));
        }

        return $event;
    }
}
