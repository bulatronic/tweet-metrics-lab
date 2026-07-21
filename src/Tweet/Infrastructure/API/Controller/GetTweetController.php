<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\DTO\TweetDTO;
use App\Tweet\Application\Query\GetTweetQuery;
use App\Tweet\Infrastructure\API\Request\TweetIdRequest;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class GetTweetController extends AbstractApiController
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
        path: '/api/tweets/{id}',
        summary: 'Получить твит по id',
        security: [['Bearer' => []]],
        tags: ['Tweets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Твит',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: new Model(type: TweetDTO::class)),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 404, description: 'Твит не найден'),
            new OA\Response(response: 422, description: 'Некорректный id'),
        ],
    )]
    #[Route('/api/tweets/{id}', name: 'api_tweets_get', methods: ['GET'])]
    public function __invoke(#[MapRoutePayload] TweetIdRequest $request): JsonResponse
    {
        /** @var TweetDTO $tweet */
        $tweet = $this->handle($this->queryBus, new GetTweetQuery($request->id));

        return $this->respondSuccess($tweet);
    }
}
