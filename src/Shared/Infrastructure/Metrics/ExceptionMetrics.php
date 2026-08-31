<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Exception\MetricsRegistrationException;

final readonly class ExceptionMetrics
{
    private const string NAMESPACE = 'tweet_metrics_lab';

    public function __construct(
        private CollectorRegistry $collectorRegistry,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function increment(string $exceptionClass, int $statusCode): void
    {
        $this->exceptionCounter()->inc([$exceptionClass, (string) $statusCode]);
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function exceptionCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'exception',
            'Total number of exceptions thrown while handling a request',
            ['class', 'status'],
        );
    }
}
