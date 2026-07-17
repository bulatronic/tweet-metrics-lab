<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Exception\InvalidPasswordHashException;
use App\User\Domain\Service\PasswordHasherInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Hashes passwords via Symfony's hasher configured for DoctrineUser.
 * Uses the factory with the user class (no dummy entity instance needed).
 */
final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    /**
     * @throws InvalidPasswordHashException
     */
    public function hash(string $plainPassword): PasswordHash
    {
        $hash = $this->passwordHasherFactory
            ->getPasswordHasher(DoctrineUser::class)
            ->hash($plainPassword);

        return PasswordHash::fromString($hash);
    }

    public function verify(PasswordHash $hash, string $plainPassword): bool
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(DoctrineUser::class)
            ->verify($hash->toString(), $plainPassword);
    }
}
