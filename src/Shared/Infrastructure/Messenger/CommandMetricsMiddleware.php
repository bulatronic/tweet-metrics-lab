<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Infrastructure\Metrics\CommandMetrics;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Records commands_handled_total and command_duration_seconds for the command bus.
 */
final readonly class CommandMetricsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CommandMetrics $commandMetrics,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $commandLabel = new \ReflectionClass($envelope->getMessage())->getShortName();
        $startedAt = microtime(true);

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->commandMetrics->observeDuration($commandLabel, microtime(true) - $startedAt);
            $this->commandMetrics->incrementHandled($commandLabel);
        }
    }
}
