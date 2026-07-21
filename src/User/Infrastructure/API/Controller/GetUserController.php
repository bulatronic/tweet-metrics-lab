<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\User\Application\DTO\UserDTO;
use App\User\Application\Query\GetUserQuery;
use App\User\Infrastructure\API\Request\UserIdRequest;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class GetUserController extends AbstractApiController
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
        path: '/api/users/{id}',
        summary: 'Получить пользователя по id',
        security: [['Bearer' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Профиль пользователя',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: new Model(type: UserDTO::class)),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 422, description: 'Некорректный id или пользователь не найден'),
        ],
    )]
    #[Route('/api/users/{id}', name: 'api_users_get', methods: ['GET'])]
    public function __invoke(#[MapRoutePayload] UserIdRequest $request): JsonResponse
    {
        /** @var UserDTO $user */
        $user = $this->handle($this->queryBus, new GetUserQuery($request->id));

        return $this->respondSuccess($user);
    }
}
