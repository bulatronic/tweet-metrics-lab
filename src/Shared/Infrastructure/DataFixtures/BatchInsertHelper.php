<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DataFixtures;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Memory-friendly multi-row INSERT helper for large fixture sets.
 */
final readonly class BatchInsertHelper
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param list<string>      $columns
     * @param list<list<mixed>> $rows
     *
     * @throws Exception
     */
    public function insert(string $table, array $columns, array $rows, int $batchSize = 500): void
    {
        if ([] === $rows) {
            return;
        }

        $columnList = implode(', ', $columns);

        foreach (array_chunk($rows, $batchSize) as $chunk) {
            $placeholders = [];
            $params = [];

            foreach ($chunk as $rowIndex => $row) {
                $rowPlaceholders = [];
                foreach (array_values($row) as $colIndex => $value) {
                    $key = sprintf('r%d_c%d', $rowIndex, $colIndex);
                    $rowPlaceholders[] = ':'.$key;
                    $params[$key] = $value;
                }
                $placeholders[] = '('.implode(', ', $rowPlaceholders).')';
            }

            implode(', ', $placeholders)
                |> (fn ($x) => sprintf('INSERT INTO %s (%s) VALUES %s', $table, $columnList, $x))
                |> (fn ($x) => $this->connection->executeStatement($x, $params));
        }
    }
}
