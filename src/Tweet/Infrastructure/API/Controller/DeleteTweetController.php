<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\Command\DeleteTweetCommand;
use App\Tweet\Infrastructure\API\Request\TweetIdRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteTweetController extends AbstractApiController
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
        path: '/api/tweets/{id}',
        summary: 'Удалить твит',
        security: [['Bearer' => []]],
        tags: ['Tweets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Твит удалён'),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 404, description: 'Твит не найден'),
            new OA\Response(response: 422, description: 'Некорректный id'),
        ],
    )]
    #[Route('/api/tweets/{id}', name: 'api_tweets_delete', methods: ['DELETE'])]
    public function __invoke(#[MapRoutePayload] TweetIdRequest $request): Response
    {
        $this->handle($this->commandBus, new DeleteTweetCommand($request->id));

        return $this->respondNoContent();
    }
}
