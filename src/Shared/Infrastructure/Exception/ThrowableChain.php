<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Exception;

use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Walks an exception chain, transparently unwrapping Messenger's HandlerFailedException.
 */
final class ThrowableChain
{
    /**
     * @return iterable<\Throwable> from the outermost exception down to the root cause
     */
    public static function unwrap(\Throwable $throwable): iterable
    {
        $current = $throwable;

        while (null !== $current) {
            yield $current;

            $current = self::previous($current);
        }
    }

    public static function rootCause(\Throwable $throwable): \Throwable
    {
        $root = $throwable;

        foreach (self::unwrap($throwable) as $current) {
            $root = $current;
        }

        return $root;
    }

    private static function previous(\Throwable $throwable): ?\Throwable
    {
        if (!$throwable instanceof HandlerFailedException) {
            return $throwable->getPrevious();
        }

        $wrapped = $throwable->getWrappedExceptions();

        return [] === $wrapped ? $throwable->getPrevious() : reset($wrapped);
    }
}
