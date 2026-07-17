<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use App\Shared\Domain\MetricsRegistryInterface;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInitTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\Exception\MetricsRegistrationException;

/**
 * Single DI-friendly facade over promphp CollectorRegistry.
 * Autoconfigured as prometheus_metrics_bundle.metrics_collector (init injects namespace + registry).
 */
final class MetricsRegistry implements MetricsCollectorInterface, MetricsRegistryInterface
{
    use MetricsCollectorInitTrait;

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementTweetsCreated(): void
    {
        $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'tweets_created_total',
            'Total number of created tweets',
        )->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementLikes(): void
    {
        $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'likes_total',
            'Total number of likes',
        )->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementUnlikes(): void
    {
        $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'unlikes_total',
            'Total number of unlikes',
        )->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementFollows(): void
    {
        $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'follows_total',
            'Total number of follows',
        )->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function incrementUnfollows(): void
    {
        $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'unfollows_total',
            'Total number of unfollows',
        )->inc();
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function setActiveUsers5m(int $count): void
    {
        $this->collectionRegistry->getOrRegisterGauge(
            $this->namespace,
            'active_users_5m',
            'Approximate number of authenticated users active in the last 5 minutes',
        )->set($count);
    }
}
