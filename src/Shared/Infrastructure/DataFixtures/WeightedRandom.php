<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DataFixtures;

/**
 * Weighted random index picker for power-law / uneven fixture distributions.
 */
final class WeightedRandom
{
    /**
     * @param array<int, int|float> $weights keyed by choice index
     */
    public static function pick(array $weights): int
    {
        if ([] === $weights) {
            throw new \InvalidArgumentException('Weights must not be empty.');
        }

        $sum = array_sum($weights);
        if ($sum <= 0) {
            throw new \InvalidArgumentException('Weights sum must be positive.');
        }

        $target = (mt_rand() / mt_getrandmax()) * $sum;
        $running = 0.0;
        $lastKey = array_key_first($weights);

        foreach ($weights as $key => $weight) {
            $running += (float) $weight;
            if ($target <= $running) {
                return (int) $key;
            }
            $lastKey = $key;
        }

        return (int) $lastKey;
    }
}
