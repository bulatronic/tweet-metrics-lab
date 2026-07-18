<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Follow\Application\Command\FollowUserCommand;
use App\Like\Application\Command\LikeTweetCommand;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\Command\CreateTweetCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Dev load generator: dispatches write commands directly (no HTTP) to exercise
 * DB, outbox, and async consumers. Periodically spikes RPS for Grafana demos.
 *
 * Not covered by automated tests (dev tooling).
 */
#[AsCommand(
    name: 'app:simulate-traffic',
    description: 'Simulate write traffic by dispatching commands at a target RPS (with periodic spikes)',
)]
final class SimulateTrafficCommand extends Command
{
    use HandleTrait;

    private const int DEFAULT_RPS = 10;
    private const int SPIKE_MULTIPLIER = 5;
    private const int SPIKE_DURATION_SECONDS = 30;
    private const int SPIKE_INTERVAL_MIN_SECONDS = 120;
    private const int SPIKE_INTERVAL_MAX_SECONDS = 180;
    private const int ID_POOL_SIZE = 200;
    private const int REFRESH_EVERY_OPS = 500;
    private const int CLEAR_EM_EVERY_OPS = 100;

    private bool $shouldStop = false;

    /** @var list<string> */
    private array $userIds = [];

    /** @var list<string> */
    private array $tweetIds = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'rps',
                null,
                InputOption::VALUE_REQUIRED,
                'Baseline requests per second (spiked x'.self::SPIKE_MULTIPLIER.' periodically)',
                (string) self::DEFAULT_RPS,
            );
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseRps = max(1, (int) $input->getOption('rps'));

        $this->registerSignalHandlers();
        $this->refreshIdPools();

        if ([] === $this->userIds) {
            $io->error('No users in database. Load fixtures first: doctrine:fixtures:load');

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Traffic simulation started (base RPS=%d, spike x%d for %ds every %d–%ds). Ctrl+C to stop.',
            $baseRps,
            self::SPIKE_MULTIPLIER,
            self::SPIKE_DURATION_SECONDS,
            self::SPIKE_INTERVAL_MIN_SECONDS,
            self::SPIKE_INTERVAL_MAX_SECONDS,
        ));

        $ops = 0;
        $spikeUntil = null;
        $nextSpikeAt = time() + random_int(self::SPIKE_INTERVAL_MIN_SECONDS, self::SPIKE_INTERVAL_MAX_SECONDS);

        while (!$this->shouldStop) {
            $now = time();

            if (null !== $spikeUntil && $now >= $spikeUntil) {
                $spikeUntil = null;
                $nextSpikeAt = $now + random_int(self::SPIKE_INTERVAL_MIN_SECONDS, self::SPIKE_INTERVAL_MAX_SECONDS);
                'H:i:s'
                    |> date(...)
                    |> (fn ($x) => sprintf('<comment>[%s] spike ended → RPS=%d</comment>', $x, $baseRps))
                    |> $io(...);
            }

            if (null === $spikeUntil && $now >= $nextSpikeAt) {
                $spikeUntil = $now + self::SPIKE_DURATION_SECONDS;
                'H:i:s'
                    |> date(...)
                    |> (fn ($x) => sprintf('<info>[%s] SPIKE → RPS=%d for %ds</info>', $x, $baseRps * self::SPIKE_MULTIPLIER, self::SPIKE_DURATION_SECONDS))
                    |> $io(...);
            }

            $effectiveRps = null !== $spikeUntil
                ? $baseRps * self::SPIKE_MULTIPLIER
                : $baseRps;
            $intervalUs = (int) max(1, intdiv(1_000_000, $effectiveRps));

            $started = hrtime(true);
            $this->dispatchRandomAction($io);
            ++$ops;

            if (0 === $ops % self::CLEAR_EM_EVERY_OPS) {
                $this->entityManager->clear();
            }

            if (0 === $ops % self::REFRESH_EVERY_OPS) {
                $this->refreshIdPools();
            }

            $elapsedUs = (int) ((hrtime(true) - $started) / 1_000);
            $sleepUs = $intervalUs - $elapsedUs;
            if ($sleepUs > 0 && !$this->shouldStop) {
                usleep($sleepUs);
            }
        }

        $io->writeln('<comment>Shutdown signal received, stopping.</comment>');

        return Command::SUCCESS;
    }

    private function dispatchRandomAction(SymfonyStyle $io): void
    {
        try {
            match (random_int(0, 2)) {
                0 => $this->createTweet(),
                1 => $this->likeTweet(),
                default => $this->followUser(),
            };
        } catch (HandlerFailedException $exception) {
            // Expected under random load (duplicate like/follow, self-follow, etc.)
            if (array_any($exception->getWrappedExceptions(), fn ($wrapped) => $wrapped instanceof DomainException)) {
                return;
            }
            $io->warning($exception->getMessage());
        } catch (DomainException) {
            // Expected under random load.
        } catch (\Throwable $exception) {
            $io->warning($exception->getMessage());
        }
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    private function createTweet(): void
    {
        $authorId = $this->pickUserId();
        $text = sprintf('sim tweet %s @%d', bin2hex(random_bytes(4)), time());

        $this->handle($this->commandBus, new CreateTweetCommand(
            authorId: $authorId,
            text: $text,
        ));
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    private function likeTweet(): void
    {
        if ([] === $this->tweetIds) {
            $this->createTweet();

            return;
        }

        $this->handle($this->commandBus, new LikeTweetCommand(
            tweetId: $this->pickTweetId(),
            userId: $this->pickUserId(),
        ));
    }

    /**
     * @throws ExceptionInterface
     */
    private function followUser(): void
    {
        $followerId = $this->pickUserId();
        $followeeId = $this->pickUserId();

        if ($followerId === $followeeId) {
            return;
        }

        $this->handle($this->commandBus, new FollowUserCommand(
            followerId: $followerId,
            followeeId: $followeeId,
        ));
    }

    private function pickUserId(): string
    {
        return $this->userIds[array_rand($this->userIds)];
    }

    private function pickTweetId(): string
    {
        return $this->tweetIds[array_rand($this->tweetIds)];
    }

    /**
     * @throws Exception
     */
    private function refreshIdPools(): void
    {
        /** @var list<string> $userIds */
        $userIds = $this->connection->fetchFirstColumn(
            'SELECT id::text FROM users ORDER BY RANDOM() LIMIT :limit',
            ['limit' => self::ID_POOL_SIZE],
            ['limit' => ParameterType::INTEGER],
        );
        $this->userIds = $userIds;

        /** @var list<string> $tweetIds */
        $tweetIds = $this->connection->fetchFirstColumn(
            'SELECT id::text FROM tweets ORDER BY RANDOM() LIMIT :limit',
            ['limit' => self::ID_POOL_SIZE],
            ['limit' => ParameterType::INTEGER],
        );
        $this->tweetIds = $tweetIds;
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
}
