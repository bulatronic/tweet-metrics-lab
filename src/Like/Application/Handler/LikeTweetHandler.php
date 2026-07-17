<?php

declare(strict_types=1);

namespace App\Like\Application\Handler;

use App\Like\Application\Command\LikeTweetCommand;
use App\Like\Domain\Entity\Like;
use App\Like\Domain\Exception\LikeAlreadyExistsException;
use App\Like\Domain\Repository\LikeRepositoryInterface;
use App\Shared\Domain\EventPublisherInterface;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Domain\Event\TweetWasLiked;
use App\Tweet\Domain\Exception\TweetNotFoundException;
use App\Tweet\Domain\Repository\TweetRepositoryInterface;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Random\RandomException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class LikeTweetHandler
{
    public function __construct(
        private LikeRepositoryInterface $likeRepository,
        private TweetRepositoryInterface $tweetRepository,
        private UserRepositoryInterface $userRepository,
        private EventPublisherInterface $eventPublisher,
    ) {
    }

    /**
     * @throws RandomException
     * @throws UserNotFoundException
     * @throws LikeAlreadyExistsException
     * @throws InvalidUuidException
     * @throws TweetNotFoundException
     */
    public function __invoke(LikeTweetCommand $command): void
    {
        $tweetId = TweetId::fromString($command->tweetId);
        $userId = UserId::fromString($command->userId);

        if (null === $this->userRepository->findById($userId)) {
            throw new UserNotFoundException($userId);
        }

        if (null === $this->tweetRepository->findById($tweetId)) {
            throw new TweetNotFoundException($tweetId);
        }

        if (null !== $this->likeRepository->findByTweetAndUser($tweetId, $userId)) {
            throw new LikeAlreadyExistsException($tweetId, $userId);
        }

        $like = Like::create($tweetId, $userId);

        $this->likeRepository->save($like);
        $this->eventPublisher->publish(new TweetWasLiked(
            $tweetId,
            $userId,
            $like->createdAt(),
        ));
    }
}
