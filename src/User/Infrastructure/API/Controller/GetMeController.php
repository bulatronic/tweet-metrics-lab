<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\User\Application\DTO\UserDTO;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class GetMeController extends AbstractApiController
{
    #[OA\Get(
        path: '/api/me',
        summary: 'Текущий пользователь',
        security: [['Bearer' => []]],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Профиль текущего пользователя',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: new Model(type: UserDTO::class)),
                ]),
            ),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
        ],
    )]
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
