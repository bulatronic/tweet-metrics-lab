<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\User\Application\DTO\UserDTO;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class GetMeController extends AbstractApiController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] DoctrineUser $user): JsonResponse
    {
        return $this->respondSuccess(new UserDTO(
            id: $user->getId()->toRfc4122(),
            email: $user->getEmail(),
            username: $user->getUsername(),
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ));
    }
}
