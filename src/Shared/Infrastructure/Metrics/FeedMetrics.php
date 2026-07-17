<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use App\Tweet\Application\FeedCacheMetricsInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Histogram;

final readonly class FeedMetrics implements FeedCacheMetricsInterface
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
    public function incrementHits(): void
    {
        $this->cacheHitsCounter()->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementMisses(): void
    {
        $this->cacheMissesCounter()->inc();
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

    /**
     * @throws MetricsRegistrationException
     */
    private function cacheHitsCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'feed_cache_hits_total',
            'Total number of feed cache hits',
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function cacheMissesCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'feed_cache_misses_total',
            'Total number of feed cache misses',
        );
    }
}
