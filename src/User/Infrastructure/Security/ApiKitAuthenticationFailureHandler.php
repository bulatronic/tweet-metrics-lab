<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use ApiKit\Response\ResponseFactoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler as LexikAuthenticationFailureHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Wraps Lexik login failure payload into api-kit error format.
 */
final readonly class ApiKitAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private LexikAuthenticationFailureHandler $inner,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $response = $this->inner->onAuthenticationFailure($request, $exception);

        return $this->responseFactory->error(
            message: $exception->getMessageKey(),
            code: 'UNAUTHORIZED',
            statusCode: $response->getStatusCode() ?: 401,
        );
    }
}
