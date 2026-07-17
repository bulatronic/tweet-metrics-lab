<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use ApiKit\Response\ResponseFactoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler as LexikAuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Wraps Lexik/gesdinet login+refresh success payload into api-kit response format.
 */
final readonly class ApiKitAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private LexikAuthenticationSuccessHandler $inner,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $response = $this->inner->onAuthenticationSuccess($request, $token);
        $payload = json_decode($response->getContent() ?: '[]', true);
        if (!\is_array($payload)) {
            $payload = [];
        }

        return $this->responseFactory->success($payload, $response->getStatusCode());
    }
}
