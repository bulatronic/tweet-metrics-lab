<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\DTO\FeedItemDTO;
use App\Tweet\Application\DTO\FeedPageDTO;
use App\Tweet\Application\Query\GetFeedQuery;
use App\Tweet\Infrastructure\API\Request\CursorQueryDto;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class GetFeedController extends AbstractApiController
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
        path: '/api/feed',
        summary: 'Лента текущего пользователя (cursor-пагинация)',
        security: [['Bearer' => []]],
        tags: ['Feed'],
        parameters: [
            new OA\Parameter(name: 'cursor', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Страница ленты',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: FeedItemDTO::class))),
                        new OA\Property(property: 'nextCursor', type: 'string', nullable: true),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ],
    )]
    #[Route('/api/feed', name: 'api_feed', methods: ['GET'])]
    public function __invoke(#[MapQueryString] CursorQueryDto $query): JsonResponse
    {
        /** @var FeedPageDTO $page */
        $page = $this->handle($this->queryBus, new GetFeedQuery(
            cursor: $query->cursor,
            limit: $query->limit,
        ));

        return $this->respondSuccess($page);
    }
}
