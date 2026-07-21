<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use ApiKit\Exception\ApiException;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\Command\CreateTweetCommand;
use App\Tweet\Infrastructure\API\Request\CreateTweetRequestDto;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CreateTweetController extends AbstractApiController
{
    use HandleTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        #[Autowire(service: 'limiter.create_tweet')]
        private readonly RateLimiterFactory $createTweetLimiter,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[OA\Post(
        path: '/api/tweets',
        summary: 'Создать твит',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateTweetRequestDto::class)),
        ),
        tags: ['Tweets'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Твит создан',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
            new OA\Response(response: 422, description: 'Ошибка валидации (пустой/слишком длинный текст)'),
            new OA\Response(response: 429, description: 'Превышен лимит создания твитов'),
        ],
    )]
    #[Route('/api/tweets', name: 'api_tweets_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateTweetRequestDto $request,
        #[CurrentUser] DoctrineUser $user,
    ): JsonResponse {
        $limiter = $this->createTweetLimiter->create($user->getId()->toRfc4122());
        if (!$limiter->consume(1)->isAccepted()) {
            throw new ApiException(429, 'Too many tweets. Try again later.', ['reason' => 'rate_limit_exceeded']);
        }

        /** @var string $tweetId */
        $tweetId = $this->handle($this->commandBus, new CreateTweetCommand(
            authorId: $user->getId()->toRfc4122(),
            text: $request->text,
        ));

        return $this->respondCreated(['id' => $tweetId]);
    }
}
