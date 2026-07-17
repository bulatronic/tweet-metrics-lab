<?php

declare(strict_types=1);

namespace App\Like\Application\Handler;

use App\Like\Application\Command\UnlikeTweetCommand;
use App\Like\Domain\Exception\LikeNotFoundException;
use App\Like\Domain\Repository\LikeRepositoryInterface;
use App\Shared\Domain\EventPublisherInterface;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Shared\Domain\TransactionManagerInterface;
use App\Tweet\Domain\Event\TweetWasUnliked;
use App\Tweet\Domain\Exception\CannotDecrementLikesException;
use App\Tweet\Domain\Exception\TweetNotFoundException;
use App\Tweet\Domain\Repository\TweetRepositoryInterface;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UnlikeTweetHandler
{
    public function __construct(
        private LikeRepositoryInterface $likeRepository,
        private TweetRepositoryInterface $tweetRepository,
        private EventPublisherInterface $eventPublisher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    /**
     * @throws CannotDecrementLikesException
     * @throws LikeNotFoundException
     * @throws InvalidUuidException
     * @throws TweetNotFoundException
     */
    public function __invoke(UnlikeTweetCommand $command): void
    {
        $tweetId = TweetId::fromString($command->tweetId);
        $userId = UserId::fromString($command->userId);

        $like = $this->likeRepository->findByTweetAndUser($tweetId, $userId);
        if (null === $like) {
            throw new LikeNotFoundException($tweetId, $userId);
        }

        $tweet = $this->tweetRepository->findById($tweetId);
        if (null === $tweet) {
            throw new TweetNotFoundException($tweetId);
        }

        $tweet->decrementLikes();
        $occurredAt = new \DateTimeImmutable();

        $this->transactionManager->transactional(function () use ($like, $tweet, $tweetId, $userId, $occurredAt): void {
            $this->likeRepository->remove($like);
            $this->tweetRepository->save($tweet);
            $this->eventPublisher->publish(new TweetWasUnliked($tweetId, $userId, $occurredAt));
        });
    }
}
