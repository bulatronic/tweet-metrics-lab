<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Infrastructure\API\Request\RegisterRequestDto;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterUserController extends AbstractApiController
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
        path: '/api/register',
        summary: 'Регистрация нового пользователя',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: RegisterRequestDto::class)),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Пользователь создан',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(response: 409, description: 'Email или username уже заняты'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ],
    )]
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] RegisterRequestDto $request): JsonResponse
    {
        /** @var string $userId */
        $userId = $this->handle($this->commandBus, new RegisterUserCommand(
            email: $request->email,
            plainPassword: $request->password,
            username: $request->username,
        ));

        return $this->respondCreated(['id' => $userId]);
    }
}
