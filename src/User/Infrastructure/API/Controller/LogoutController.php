<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\User\Infrastructure\Security\JwtTokenBlacklist;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Blacklists access-token jti in Redis (TTL = remaining lifetime) and invalidates refresh tokens.
 */
final class LogoutController extends AbstractApiController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TokenExtractorInterface $tokenExtractor,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly JwtTokenBlacklist $jwtBlacklist,
    ) {
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Выход: заносит JWT в blacklist и гасит refresh-токен',
        security: [['Bearer' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 204, description: 'Выход выполнен'),
            new OA\Response(response: 401, description: 'Не аутентифицирован'),
        ],
    )]
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $rawToken = $this->tokenExtractor->extract($request);
        if (false !== $rawToken && '' !== $rawToken) {
            try {
                $payload = $this->jwtManager->parse($rawToken);
                $jti = $payload['jti'] ?? null;
                $exp = $payload['exp'] ?? null;
                if (\is_string($jti) && '' !== $jti && (\is_int($exp) || \is_float($exp) || \is_string($exp))) {
                    $ttl = (int) $exp - time();
                    $this->jwtBlacklist->add($jti, $ttl);
                }
            } catch (JWTDecodeFailureException) {
                // Token already invalid — nothing to blacklist.
            }
        }

        $this->eventDispatcher->dispatch(new LogoutEvent($request, $this->tokenStorage->getToken()));
        $this->tokenStorage->setToken(null);

        return $this->respondNoContent();
    }
}
