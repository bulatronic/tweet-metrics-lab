<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\InvalidPasswordHashException;
use App\User\Domain\Exception\InvalidUsernameException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Username;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function save(User $user): void
    {
        $existing = $this->entityManager->find(
            DoctrineUser::class,
            Uuid::fromString($user->id()->toString()),
        );

        if (null === $existing) {
            $this->entityManager->persist($this->fromDomain($user));
        } else {
            $existing->sync(
                $user->email()->toString(),
                $user->passwordHash()->toString(),
                $user->username()->toString(),
            );
        }

        $this->entityManager->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws InvalidUsernameException
     * @throws ORMException
     * @throws InvalidPasswordHashException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    public function findById(UserId $id): ?User
    {
        $entity = $this->entityManager->find(
            DoctrineUser::class,
            Uuid::fromString($id->toString()),
        );

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws InvalidUsernameException
     * @throws InvalidPasswordHashException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    public function findByEmail(Email $email): ?User
    {
        $entity = $this->entityManager->getRepository(DoctrineUser::class)->findOneBy([
            'email' => $email->toString(),
        ]);

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws InvalidUsernameException
     * @throws InvalidPasswordHashException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    public function findByUsername(Username $username): ?User
    {
        $entity = $this->entityManager->getRepository(DoctrineUser::class)->findOneBy([
            'username' => $username->toString(),
        ]);

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws InvalidUsernameException
     * @throws InvalidPasswordHashException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    private function toDomain(DoctrineUser $entity): User
    {
        return User::reconstitute(
            UserId::fromString($entity->getId()->toRfc4122()),
            Email::fromString($entity->getEmail()),
            PasswordHash::fromString($entity->getPasswordHash()),
            Username::fromString($entity->getUsername()),
            $entity->getCreatedAt(),
        );
    }

    private function fromDomain(User $user): DoctrineUser
    {
        return new DoctrineUser(
            Uuid::fromString($user->id()->toString()),
            $user->email()->toString(),
            $user->passwordHash()->toString(),
            $user->username()->toString(),
            $user->createdAt(),
        );
    }
}
