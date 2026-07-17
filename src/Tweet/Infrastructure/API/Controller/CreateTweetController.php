<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\Command\CreateTweetCommand;
use App\Tweet\Infrastructure\API\Request\CreateTweetRequestDto;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CreateTweetController extends AbstractApiController
{
    use HandleTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/api/tweets', name: 'api_tweets_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateTweetRequestDto $request,
        #[CurrentUser] DoctrineUser $user,
    ): JsonResponse {
        /** @var string $tweetId */
        $tweetId = $this->handle($this->commandBus, new CreateTweetCommand(
            authorId: $user->getId()->toRfc4122(),
            text: $request->text,
        ));

        return $this->respondCreated(['id' => $tweetId]);
    }
}
