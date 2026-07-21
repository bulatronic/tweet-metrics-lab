<?php

declare(strict_types=1);

namespace App\Like\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Like\Application\Command\UnlikeTweetCommand;
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

final class UnlikeTweetController extends AbstractApiController
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
        path: '/api/tweets/{id}/like',
        summary: 'Убрать лайк с твита',
        security: [['Bearer' => []]],
        tags: ['Likes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Лайк убран'),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 404, description: 'Лайк или твит не найден'),
            new OA\Response(response: 422, description: 'Некорректный id'),
        ],
    )]
    #[Route('/api/tweets/{id}/like', name: 'api_tweets_unlike', methods: ['DELETE'])]
    public function __invoke(
        #[MapRoutePayload] TweetIdRequest $request,
        #[CurrentUser] DoctrineUser $user,
    ): Response {
        $this->handle($this->commandBus, new UnlikeTweetCommand(
            tweetId: $request->id,
            userId: $user->getId()->toRfc4122(),
        ));

        return $this->respondNoContent();
    }
}
