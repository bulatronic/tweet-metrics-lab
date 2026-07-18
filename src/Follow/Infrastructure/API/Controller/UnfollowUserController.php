<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Follow\Application\Command\UnfollowUserCommand;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\User\Infrastructure\API\Request\UserIdRequest;
use App\User\Infrastructure\Persistence\DoctrineUser;
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
