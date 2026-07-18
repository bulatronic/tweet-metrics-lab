<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Follow\Application\DTO\FollowListDTO;
use App\Follow\Application\Query\GetFollowingQuery;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\User\Infrastructure\API\Request\CursorQueryDto;
use App\User\Infrastructure\API\Request\UserIdRequest;
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
