<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Gauge;
use Prometheus\Histogram;

final readonly class OutboxMetrics
{
    private const string NAMESPACE = 'tweet_metrics_lab';

    public function __construct(
        private CollectorRegistry $collectorRegistry,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function setPendingMessages(int $count): void
    {
        $this->pendingGauge()->set($count);
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function observeRelayBatchDuration(float $seconds): void
    {
        $this->relayBatchHistogram()->observe($seconds);
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementPublished(): void
    {
        $this->publishedCounter()->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementFailed(): void
    {
        $this->failedCounter()->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementDead(): void
    {
        $this->deadCounter()->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function observeMessageLag(float $seconds): void
    {
        $this->messageLagHistogram()->observe($seconds);
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function pendingGauge(): Gauge
    {
        return $this->collectorRegistry->getOrRegisterGauge(
            self::NAMESPACE,
            'outbox_pending_messages',
            'Number of unpublished outbox messages',
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function relayBatchHistogram(): Histogram
    {
        return $this->collectorRegistry->getOrRegisterHistogram(
            self::NAMESPACE,
            'outbox_relay_batch_duration_seconds',
            'Duration of one outbox relay batch in seconds',
            [],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0],
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function publishedCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'outbox_messages_published_total',
            'Total number of successfully published outbox messages',
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function failedCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'outbox_messages_failed_total',
            'Total number of failed outbox relay attempts',
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function deadCounter(): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            self::NAMESPACE,
            'outbox_messages_dead_total',
            'Total number of outbox messages moved to dead state after max attempts',
        );
    }

    /**
     * @throws MetricsRegistrationException
     */
    private function messageLagHistogram(): Histogram
    {
        return $this->collectorRegistry->getOrRegisterHistogram(
            self::NAMESPACE,
            'outbox_message_lag_seconds',
            'Lag between outbox message creation and successful publish',
            [],
            [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0, 30.0, 60.0],
        );
    }
}
