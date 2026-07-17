<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Elasticsearch;

/**
 * Shared index name / mapping for the tweets search projection.
 */
final class TweetsIndex
{
    public const string NAME = 'tweets';

    public static function mapping(): array
    {
        return [
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'text' => ['type' => 'text'],
                    'authorId' => ['type' => 'keyword'],
                    'authorUsername' => ['type' => 'keyword'],
                    'createdAt' => ['type' => 'date'],
                ],
            ],
        ];
    }
}
