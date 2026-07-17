<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Histogram;

final readonly class CommandMetrics
{
    private const string NAMESPACE = 'tweet_metrics_lab';

    public function __construct(
        private CollectorRegistry $collectorRegistry,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementHandled(string $command): void
    {
        $this->handledCounter()->inc([$command]);
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function observeDuration(string $command, float $seconds): void
    {
        $this->durationHistogram()->observe($seconds, [$command]);
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function handledCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'commands_handled_total',
            'Total number of handled application commands',
            ['command'],
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function durationHistogram(): Histogram
    {
        return $this->collectorRegistry->getOrRegisterHistogram(
            self::NAMESPACE,
            'command_duration_seconds',
            'Duration of application command handling in seconds',
            ['command'],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0],
        );
    }
}
