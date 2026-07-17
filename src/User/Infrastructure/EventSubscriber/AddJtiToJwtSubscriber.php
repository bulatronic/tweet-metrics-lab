<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Random\RandomException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ensures every access token carries a jti claim (needed for logout blacklist).
 */
final class AddJtiToJwtSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJwtCreated',
        ];
    }

    /**
     * @throws RandomException
     */
    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        if (!isset($payload['jti']) || !\is_string($payload['jti']) || '' === $payload['jti']) {
            $payload['jti'] = bin2hex(random_bytes(16));
            $event->setData($payload);
        }
    }
}
