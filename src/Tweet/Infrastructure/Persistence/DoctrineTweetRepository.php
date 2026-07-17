<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\Persistence;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Domain\Entity\Tweet;
use App\Tweet\Domain\Exception\TweetTextTooLongException;
use App\Tweet\Domain\Repository\TweetRepositoryInterface;
use App\Tweet\Domain\ValueObject\TweetId;
use App\Tweet\Domain\ValueObject\TweetText;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineTweetRepository implements TweetRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function save(Tweet $tweet): void
    {
        $existing = $this->entityManager->find(
            DoctrineTweet::class,
            Uuid::fromString($tweet->id()->toString()),
        );

        if (null === $existing) {
            $this->entityManager->persist($this->fromDomain($tweet));
        } else {
            $existing->sync(
                $tweet->text()->toString(),
                $tweet->likesCount(),
            );
        }

        $this->entityManager->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TweetTextTooLongException
     * @throws InvalidUuidException
     */
    public function findById(TweetId $id): ?Tweet
    {
        $entity = $this->entityManager->find(
            DoctrineTweet::class,
            Uuid::fromString($id->toString()),
        );

        return null === $entity ? null : $this->toDomain($entity);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function delete(Tweet $tweet): void
    {
        $existing = $this->entityManager->find(
            DoctrineTweet::class,
            Uuid::fromString($tweet->id()->toString()),
        );

        if (null !== $existing) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws TweetTextTooLongException
     * @throws InvalidUuidException
     */
    private function toDomain(DoctrineTweet $entity): Tweet
    {
        return Tweet::reconstitute(
            TweetId::fromString($entity->getId()->toRfc4122()),
            UserId::fromString($entity->getAuthorId()->toRfc4122()),
            TweetText::fromString($entity->getText()),
            $entity->getCreatedAt(),
            $entity->getLikesCount(),
        );
    }

    private function fromDomain(Tweet $tweet): DoctrineTweet
    {
        return new DoctrineTweet(
            Uuid::fromString($tweet->id()->toString()),
            Uuid::fromString($tweet->authorId()->toString()),
            $tweet->text()->toString(),
            $tweet->createdAt(),
            $tweet->likesCount(),
        );
    }
}
