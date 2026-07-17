<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\User\Infrastructure\Persistence\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Deletes all refresh tokens for the logging-out user.
 * Access-token jti blacklist is handled by Lexik BlockJWTListener when blocklist_token is enabled.
 */
final readonly class InvalidateRefreshTokensOnLogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $this->entityManager->createQueryBuilder()
            ->delete(RefreshToken::class, 'r')
            ->where('r.username = :username')
            ->setParameter('username', $user->getUserIdentifier())
            ->getQuery()
            ->execute();
    }
}
