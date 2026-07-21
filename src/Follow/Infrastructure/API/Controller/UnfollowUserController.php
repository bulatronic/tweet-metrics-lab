<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Follow\Application\Command\UnfollowUserCommand;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\User\Infrastructure\API\Request\UserIdRequest;
use App\User\Infrastructure\Persistence\DoctrineUser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UnfollowUserController extends AbstractApiController
{
    use HandleTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[OA\Delete(
        path: '/api/users/{id}/follow',
        summary: 'Отписаться от пользователя',
        security: [['Bearer' => []]],
        tags: ['Follows'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Отписка выполнена'),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 404, description: 'Подписка не найдена'),
            new OA\Response(response: 422, description: 'Некорректный id'),
        ],
    )]
    #[Route('/api/users/{id}/follow', name: 'api_users_unfollow', methods: ['DELETE'])]
    public function __invoke(
        #[MapRoutePayload] UserIdRequest $request,
        #[CurrentUser] DoctrineUser $user,
    ): Response {
        $this->handle($this->commandBus, new UnfollowUserCommand(
            followerId: $user->getId()->toRfc4122(),
            followeeId: $request->id,
        ));

        return $this->respondNoContent();
    }
}
