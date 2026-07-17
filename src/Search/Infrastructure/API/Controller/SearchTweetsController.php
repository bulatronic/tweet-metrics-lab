<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Search\Application\DTO\SearchResultsDTO;
use App\Search\Application\Query\GetSearchResultsQuery;
use App\Search\Infrastructure\API\Request\SearchTweetsQueryDto;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class SearchTweetsController extends AbstractApiController
{
    use HandleTrait;

    public function __construct(
        #[Autowire(service: 'query.bus')]
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/api/search/tweets', name: 'api_search_tweets', methods: ['GET'])]
    public function __invoke(#[MapQueryString] SearchTweetsQueryDto $query): JsonResponse
    {
        /** @var SearchResultsDTO $results */
        $results = $this->handle($this->queryBus, new GetSearchResultsQuery(
            q: $query->q,
            from: $query->from,
            size: $query->size,
        ));

        return $this->respondSuccess($results);
    }
}
