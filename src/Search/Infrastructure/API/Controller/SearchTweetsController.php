<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Search\Application\DTO\SearchHitDTO;
use App\Search\Application\DTO\SearchResultsDTO;
use App\Search\Application\Query\GetSearchResultsQuery;
use App\Search\Infrastructure\API\Request\SearchTweetsQueryDto;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
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
    #[OA\Get(
        path: '/api/search/tweets',
        summary: 'Поиск твитов в Elasticsearch',
        security: [['Bearer' => []]],
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string', minLength: 1, maxLength: 280)),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 0, minimum: 0, maximum: 10000)),
            new OA\Parameter(name: 'size', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Результаты поиска',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: SearchHitDTO::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'from', type: 'integer'),
                        new OA\Property(property: 'size', type: 'integer'),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ],
    )]
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
