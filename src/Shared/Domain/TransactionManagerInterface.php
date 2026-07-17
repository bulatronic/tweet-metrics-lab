<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Port for wrapping Application write-side work in a single DB transaction
 * (aggregate changes + outbox), without leaking Doctrine into handlers.
 */
interface TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function transactional(callable $callback): mixed;
}
