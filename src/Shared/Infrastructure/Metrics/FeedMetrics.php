<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Histogram;

final readonly class FeedMetrics
{
    private const string NAMESPACE = 'tweet_metrics_lab';

    public function __construct(
        private CollectorRegistry $collectorRegistry,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function observeProjectionLag(float $seconds): void
    {
        $this->projectionLagHistogram()->observe($seconds);
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function projectionLagHistogram(): Histogram
    {
        return $this->collectorRegistry->getOrRegisterHistogram(
            self::NAMESPACE,
            'feed_projection_lag_seconds',
            'Lag between tweet creation and feed projection processing',
            [],
            [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0, 30.0, 60.0],
        );
    }
}
