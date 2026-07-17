<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\DTO\TweetPageDTO;
use App\Tweet\Application\Query\GetUserTweetsQuery;
use App\User\Infrastructure\API\Request\CursorQueryDto;
use App\User\Infrastructure\API\Request\UserIdRequest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
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
