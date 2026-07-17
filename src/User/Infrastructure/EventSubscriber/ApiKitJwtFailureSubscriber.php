<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use ApiKit\Response\ResponseFactoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replaces Lexik default JWT error JSON with api-kit error envelope.
 */
final readonly class ApiKitJwtFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_INVALID => 'onJwtFailure',
            Events::JWT_NOT_FOUND => 'onJwtFailure',
            Events::JWT_EXPIRED => 'onJwtFailure',
        ];
    }

    public function onJwtFailure(JWTInvalidEvent|JWTNotFoundEvent|JWTExpiredEvent $event): void
    {
        $previous = $event->getResponse();
        $status = $previous instanceof JWTAuthenticationFailureResponse
            ? $previous->getStatusCode()
            : 401;
        $message = $previous instanceof JWTAuthenticationFailureResponse
            ? $previous->getMessage()
            : 'Invalid JWT Token';

        $event->setResponse($this->responseFactory->error(
            message: $message,
            code: 'UNAUTHORIZED',
            statusCode: $status,
        ));
    }
}
