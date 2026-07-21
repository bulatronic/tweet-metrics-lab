<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Follow\Application\DTO\FollowListDTO;
use App\Follow\Application\DTO\FollowUserDTO;
use App\Follow\Application\Query\GetFollowingQuery;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
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

final class GetFollowingController extends AbstractApiController
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
        path: '/api/users/{id}/following',
        summary: 'На кого подписан пользователь (cursor-пагинация)',
        security: [['Bearer' => []]],
        tags: ['Follows'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'cursor', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список подписок',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: FollowUserDTO::class))),
                        new OA\Property(property: 'nextCursor', type: 'string', nullable: true),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ],
    )]
    #[Route('/api/users/{id}/following', name: 'api_users_following', methods: ['GET'])]
    public function __invoke(
        #[MapRoutePayload] UserIdRequest $request,
        #[MapQueryString] CursorQueryDto $query,
    ): JsonResponse {
        /** @var FollowListDTO $list */
        $list = $this->handle($this->queryBus, new GetFollowingQuery(
            userId: $request->id,
            cursor: $query->cursor,
            limit: $query->limit,
        ));

        return $this->respondSuccess($list);
    }
}
