<?php

declare(strict_types=1);

namespace App\Like\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Like\Application\Command\LikeTweetCommand;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Infrastructure\API\Request\TweetIdRequest;
use App\User\Infrastructure\Persistence\DoctrineUser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class LikeTweetController extends AbstractApiController
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
        path: '/api/tweets/{id}/like',
        summary: 'Лайкнуть твит',
        security: [['Bearer' => []]],
        tags: ['Likes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Лайк поставлен'),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 404, description: 'Твит не найден'),
            new OA\Response(response: 409, description: 'Лайк уже существует'),
            new OA\Response(response: 422, description: 'Некорректный id'),
        ],
    )]
    #[Route('/api/tweets/{id}/like', name: 'api_tweets_like', methods: ['POST'])]
    public function __invoke(
        #[MapRoutePayload] TweetIdRequest $request,
        #[CurrentUser] DoctrineUser $user,
    ): Response {
        $this->handle($this->commandBus, new LikeTweetCommand(
            tweetId: $request->id,
            userId: $user->getId()->toRfc4122(),
        ));

        return $this->respondNoContent();
    }
}
