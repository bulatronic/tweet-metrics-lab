<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\DTO\TweetDTO;
use App\Tweet\Application\DTO\TweetPageDTO;
use App\Tweet\Application\Query\GetUserTweetsQuery;
use App\User\Infrastructure\API\Request\CursorQueryDto;
use App\User\Infrastructure\API\Request\UserIdRequest;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class GetUserTweetsController extends AbstractApiController
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
        path: '/api/users/{id}/tweets',
        summary: 'Твиты пользователя (cursor-пагинация)',
        security: [['Bearer' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'cursor', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Страница твитов',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: TweetDTO::class))),
                        new OA\Property(property: 'nextCursor', type: 'string', nullable: true),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ],
    )]
    #[Route('/api/users/{id}/tweets', name: 'api_users_tweets', methods: ['GET'])]
    public function __invoke(
        #[MapRoutePayload] UserIdRequest $request,
        #[MapQueryString] CursorQueryDto $query,
    ): JsonResponse {
        /** @var TweetPageDTO $page */
        $page = $this->handle($this->queryBus, new GetUserTweetsQuery(
            userId: $request->id,
            cursor: $query->cursor,
            limit: $query->limit,
        ));

        return $this->respondSuccess($page);
    }
}
