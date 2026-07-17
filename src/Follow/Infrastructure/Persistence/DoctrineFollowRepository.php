<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\Persistence;

use App\Follow\Domain\Entity\Follow;
use App\Follow\Domain\Repository\FollowRepositoryInterface;
use App\Follow\Domain\ValueObject\FollowId;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineFollowRepository implements FollowRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function save(Follow $follow): void
    {
        $existing = $this->entityManager->find(
            DoctrineFollow::class,
            Uuid::fromString($follow->id()->toString()),
        );

        if (null === $existing) {
            $this->entityManager->persist($this->fromDomain($follow));
            $this->entityManager->flush();
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function remove(Follow $follow): void
    {
        $existing = $this->entityManager->find(
            DoctrineFollow::class,
            Uuid::fromString($follow->id()->toString()),
        );

        if (null !== $existing) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws InvalidUuidException
     * @throws ORMException
     */
    public function findById(FollowId $id): ?Follow
    {
        $entity = $this->entityManager->find(
            DoctrineFollow::class,
            Uuid::fromString($id->toString()),
        );

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws InvalidUuidException
     */
    public function findByFollowerAndFollowee(UserId $followerId, UserId $followeeId): ?Follow
    {
        $entity = $this->entityManager->getRepository(DoctrineFollow::class)->findOneBy([
            'followerId' => Uuid::fromString($followerId->toString()),
            'followeeId' => Uuid::fromString($followeeId->toString()),
        ]);

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws InvalidUuidException
     */
    private function toDomain(DoctrineFollow $entity): Follow
    {
        return Follow::reconstitute(
            FollowId::fromString($entity->getId()->toRfc4122()),
            UserId::fromString($entity->getFollowerId()->toRfc4122()),
            UserId::fromString($entity->getFolloweeId()->toRfc4122()),
            $entity->getCreatedAt(),
        );
    }

    private function fromDomain(Follow $follow): DoctrineFollow
    {
        return new DoctrineFollow(
            Uuid::fromString($follow->id()->toString()),
            Uuid::fromString($follow->followerId()->toString()),
            Uuid::fromString($follow->followeeId()->toString()),
            $follow->createdAt(),
        );
    }
}
