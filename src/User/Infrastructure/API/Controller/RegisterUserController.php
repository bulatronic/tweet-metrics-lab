<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Infrastructure\API\Request\RegisterRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterUserController extends AbstractApiController
{
    use HandleTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

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
