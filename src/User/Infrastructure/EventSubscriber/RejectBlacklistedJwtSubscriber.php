<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\User\Infrastructure\Security\JwtTokenBlacklist;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Rejects access tokens whose jti was blacklisted on logout.
 */
final readonly class RejectBlacklistedJwtSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JwtTokenBlacklist $blacklist,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_DECODED => 'onJwtDecoded',
        ];
    }

    public function onJwtDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();
        $jti = $payload['jti'] ?? null;
        if (!\is_string($jti) || '' === $jti) {
            return;
        }

        if ($this->blacklist->contains($jti)) {
            $event->markAsInvalid();
        }
    }
}
