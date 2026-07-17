<?php

declare(strict_types=1);

namespace App\Search\Application\Handler;

use App\Search\Application\DTO\SearchHitDTO;
use App\Search\Application\DTO\SearchResultsDTO;
use App\Search\Application\Query\GetSearchResultsQuery;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Simple match query over tweets.text with from/size pagination (no aggregations / scroll / PIT).
 */
#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetSearchResultsHandler
{
    private const string INDEX = 'tweets';
    private const int MAX_SIZE = 50;

    public function __construct(
        private Client $elasticsearch,
    ) {
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function __invoke(GetSearchResultsQuery $query): SearchResultsDTO
    {
        $from = max(0, $query->from);
        $size = max(1, min(self::MAX_SIZE, $query->size));
        $q = trim($query->q);

        if ('' === $q) {
            return new SearchResultsDTO([], 0, $from, $size);
        }

        /** @var array<mixed> $body */
        $body = [
            'from' => $from,
            'size' => $size,
            'query' => [
                'match' => [
                    'text' => $q,
                ],
            ],
        ];

        $response = $this->elasticsearch->search([
            'index' => self::INDEX,
            'body' => $body,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $response->asArray();

        $total = 0;
        $totalRaw = $payload['hits']['total'] ?? 0;
        if (\is_array($totalRaw) && isset($totalRaw['value'])) {
            $total = (int) $totalRaw['value'];
        } elseif (\is_int($totalRaw) || \is_float($totalRaw) || \is_string($totalRaw)) {
            $total = (int) $totalRaw;
        }

        $items = [];
        /** @var list<array<string, mixed>> $hits */
        $hits = $payload['hits']['hits'] ?? [];
        foreach ($hits as $hit) {
            /** @var array<string, mixed> $source */
            $source = $hit['_source'] ?? [];
            $items[] = new SearchHitDTO(
                id: (string) ($source['id'] ?? $hit['_id'] ?? ''),
                text: (string) ($source['text'] ?? ''),
                authorId: (string) ($source['authorId'] ?? ''),
                authorUsername: (string) ($source['authorUsername'] ?? ''),
                createdAt: (string) ($source['createdAt'] ?? ''),
                score: isset($hit['_score']) ? (float) $hit['_score'] : null,
            );
        }

        return new SearchResultsDTO($items, $total, $from, $size);
    }
}
