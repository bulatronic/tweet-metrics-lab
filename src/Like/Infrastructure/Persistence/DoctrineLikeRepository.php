<?php

declare(strict_types=1);

namespace App\Like\Infrastructure\Persistence;

use App\Like\Domain\Entity\Like;
use App\Like\Domain\Repository\LikeRepositoryInterface;
use App\Like\Domain\ValueObject\LikeId;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineLikeRepository implements LikeRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function save(Like $like): void
    {
        $existing = $this->entityManager->find(
            DoctrineLike::class,
            Uuid::fromString($like->id()->toString()),
        );

        if (null === $existing) {
            $this->entityManager->persist($this->fromDomain($like));
            $this->entityManager->flush();
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function remove(Like $like): void
    {
        $existing = $this->entityManager->find(
            DoctrineLike::class,
            Uuid::fromString($like->id()->toString()),
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
    public function findById(LikeId $id): ?Like
    {
        $entity = $this->entityManager->find(
            DoctrineLike::class,
            Uuid::fromString($id->toString()),
        );

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws InvalidUuidException
     */
    public function findByTweetAndUser(TweetId $tweetId, UserId $userId): ?Like
    {
        $entity = $this->entityManager->getRepository(DoctrineLike::class)->findOneBy([
            'tweetId' => Uuid::fromString($tweetId->toString()),
            'userId' => Uuid::fromString($userId->toString()),
        ]);

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws InvalidUuidException
     */
    private function toDomain(DoctrineLike $entity): Like
    {
        return Like::reconstitute(
            LikeId::fromString($entity->getId()->toRfc4122()),
            TweetId::fromString($entity->getTweetId()->toRfc4122()),
            UserId::fromString($entity->getUserId()->toRfc4122()),
            $entity->getCreatedAt(),
        );
    }

    private function fromDomain(Like $like): DoctrineLike
    {
        return new DoctrineLike(
            Uuid::fromString($like->id()->toString()),
            Uuid::fromString($like->tweetId()->toString()),
            Uuid::fromString($like->userId()->toString()),
            $like->createdAt(),
        );
    }
}
