<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Publishes domain events. Application handlers depend on this port;
 * Infrastructure writes to the outbox (same DB transaction as the aggregate).
 */
interface EventPublisherInterface
{
    public function publish(object $domainEvent): void;
}
