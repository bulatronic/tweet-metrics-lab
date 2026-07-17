<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Histogram;

final readonly class ElasticsearchMetrics
{
    private const string NAMESPACE = 'tweet_metrics_lab';

    public function __construct(
        private CollectorRegistry $collectorRegistry,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function observeIndexDuration(float $seconds): void
    {
        $this->indexDurationHistogram()->observe($seconds);
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementIndexErrors(): void
    {
        $this->indexErrorsCounter()->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function indexDurationHistogram(): Histogram
    {
        return $this->collectorRegistry->getOrRegisterHistogram(
            self::NAMESPACE,
            'elasticsearch_index_duration_seconds',
            'Duration of indexing a tweet document into Elasticsearch',
            [],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0],
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function indexErrorsCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'elasticsearch_index_errors_total',
            'Total number of failed Elasticsearch tweet index operations',
        );
    }
}
