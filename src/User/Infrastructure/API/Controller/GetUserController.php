<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\User\Application\DTO\UserDTO;
use App\User\Application\Query\GetUserQuery;
use App\User\Infrastructure\API\Request\UserIdRequest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/api/users/{id}', name: 'api_users_get', methods: ['GET'])]
    public function __invoke(#[MapRoutePayload] UserIdRequest $request): JsonResponse
    {
        /** @var UserDTO $user */
        $user = $this->handle($this->queryBus, new GetUserQuery($request->id));

        return $this->respondSuccess($user);
    }
}
