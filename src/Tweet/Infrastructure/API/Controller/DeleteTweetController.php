<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\Command\DeleteTweetCommand;
use App\Tweet\Infrastructure\API\Request\TweetIdRequest;
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
    #[Route('/api/tweets/{id}', name: 'api_tweets_delete', methods: ['DELETE'])]
    public function __invoke(#[MapRoutePayload] TweetIdRequest $request): Response
    {
        $this->handle($this->commandBus, new DeleteTweetCommand($request->id));

        return $this->respondNoContent();
    }
}
