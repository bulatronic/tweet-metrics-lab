<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Follow\Application\Command\FollowUserCommand;
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

final class FollowUserController extends AbstractApiController
{
    use HandleTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[OA\Post(
        path: '/api/users/{id}/follow',
        summary: 'Подписаться на пользователя',
        security: [['Bearer' => []]],
        tags: ['Follows'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Подписка оформлена'),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 404, description: 'Пользователь не найден'),
            new OA\Response(response: 409, description: 'Подписка уже существует'),
            new OA\Response(response: 422, description: 'Нельзя подписаться на себя / некорректный id'),
        ],
    )]
    #[Route('/api/users/{id}/follow', name: 'api_users_follow', methods: ['POST'])]
    public function __invoke(
        #[MapRoutePayload] UserIdRequest $request,
        #[CurrentUser] DoctrineUser $user,
    ): Response {
        $this->handle($this->commandBus, new FollowUserCommand(
            followerId: $user->getId()->toRfc4122(),
            followeeId: $request->id,
        ));

        return $this->respondNoContent();
    }
}
