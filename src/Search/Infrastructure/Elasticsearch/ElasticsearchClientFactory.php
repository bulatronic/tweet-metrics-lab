<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

final class ElasticsearchClientFactory
{
    /**
     * @throws AuthenticationException
     */
    public static function create(string $url): Client
    {
        return ClientBuilder::create()
            ->setHosts([$url])
            ->build();
    }
}
