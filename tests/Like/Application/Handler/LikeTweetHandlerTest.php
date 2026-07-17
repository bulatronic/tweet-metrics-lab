<?php

declare(strict_types=1);

namespace App\Tests\Like\Application\Handler;

use App\Like\Application\Command\LikeTweetCommand;
use App\Like\Application\Handler\LikeTweetHandler;
use App\Like\Domain\Entity\Like;
use App\Like\Domain\Exception\LikeAlreadyExistsException;
use App\Like\Domain\Repository\LikeRepositoryInterface;
use App\Shared\Domain\EventPublisherInterface;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Shared\Domain\MetricsRegistryInterface;
use App\Tweet\Domain\Entity\Tweet;
use App\Tweet\Domain\Event\TweetWasLiked;
use App\Tweet\Domain\Exception\TweetNotFoundException;
use App\Tweet\Domain\Exception\TweetTextTooLongException;
use App\Tweet\Domain\Repository\TweetRepositoryInterface;
use App\Tweet\Domain\ValueObject\TweetId;
use App\Tweet\Domain\ValueObject\TweetText;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\InvalidPasswordHashException;
use App\User\Domain\Exception\InvalidUsernameException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

final class LikeTweetHandlerTest extends TestCase
{
    /**
     * @throws RandomException
     * @throws InvalidUsernameException
     * @throws UserNotFoundException
     * @throws TweetTextTooLongException
     * @throws TweetNotFoundException
     * @throws InvalidPasswordHashException
     * @throws LikeAlreadyExistsException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    public function testPublishesLikedEventWithoutUpdatingTweetCounters(): void
    {
        $user = User::reconstitute(
            UserId::generate(),
            Email::fromString('a@example.com'),
            PasswordHash::fromString('$hash'),
            Username::fromString('alice'),
            new \DateTimeImmutable(),
        );
        $tweet = Tweet::create($user->id(), TweetText::fromString('hello'));

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);

        $tweetRepository = $this->createMock(TweetRepositoryInterface::class);
        $tweetRepository->method('findById')->willReturn($tweet);
        $tweetRepository->expects($this->never())->method('save');

        $likeRepository = $this->createMock(LikeRepositoryInterface::class);
        $likeRepository->method('findByTweetAndUser')->willReturn(null);
        $likeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Like::class));

        $eventPublisher = $this->createMock(EventPublisherInterface::class);
        $eventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function (object $event) use ($tweet, $user): bool {
                return $event instanceof TweetWasLiked
                    && $event->tweetId->equals($tweet->id())
                    && $event->userId->equals($user->id());
            }));

        $metrics = $this->createMock(MetricsRegistryInterface::class);
        $metrics->expects($this->once())->method('incrementLikes');

        $handler = new LikeTweetHandler(
            $likeRepository,
            $tweetRepository,
            $userRepository,
            $eventPublisher,
            $metrics,
        );

        $handler(new LikeTweetCommand(
            $tweet->id()->toString(),
            $user->id()->toString(),
        ));
    }

    /**
     * @throws RandomException
     * @throws LikeAlreadyExistsException
     * @throws InvalidUuidException
     * @throws TweetNotFoundException
     */
    public function testThrowsWhenUserMissing(): void
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn(null);

        $handler = new LikeTweetHandler(
            $this->createStub(LikeRepositoryInterface::class),
            $this->createStub(TweetRepositoryInterface::class),
            $userRepository,
            $this->createStub(EventPublisherInterface::class),
            $this->createStub(MetricsRegistryInterface::class),
        );

        $this->expectException(UserNotFoundException::class);

        $handler(new LikeTweetCommand(
            TweetId::generate()->toString(),
            UserId::generate()->toString(),
        ));
    }

    /**
     * @throws RandomException
     * @throws InvalidUsernameException
     * @throws UserNotFoundException
     * @throws InvalidPasswordHashException
     * @throws LikeAlreadyExistsException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    public function testThrowsWhenTweetMissing(): void
    {
        $user = User::reconstitute(
            UserId::generate(),
            Email::fromString('a@example.com'),
            PasswordHash::fromString('$hash'),
            Username::fromString('alice'),
            new \DateTimeImmutable(),
        );

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);

        $tweetRepository = $this->createStub(TweetRepositoryInterface::class);
        $tweetRepository->method('findById')->willReturn(null);

        $handler = new LikeTweetHandler(
            $this->createStub(LikeRepositoryInterface::class),
            $tweetRepository,
            $userRepository,
            $this->createStub(EventPublisherInterface::class),
            $this->createStub(MetricsRegistryInterface::class),
        );

        $this->expectException(TweetNotFoundException::class);

        $handler(new LikeTweetCommand(
            TweetId::generate()->toString(),
            $user->id()->toString(),
        ));
    }

    /**
     * @throws RandomException
     * @throws InvalidUsernameException
     * @throws UserNotFoundException
     * @throws TweetTextTooLongException
     * @throws TweetNotFoundException
     * @throws InvalidPasswordHashException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    public function testThrowsWhenLikeAlreadyExists(): void
    {
        $user = User::reconstitute(
            UserId::generate(),
            Email::fromString('a@example.com'),
            PasswordHash::fromString('$hash'),
            Username::fromString('alice'),
            new \DateTimeImmutable(),
        );
        $tweet = Tweet::create($user->id(), TweetText::fromString('hello'));

        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findById')->willReturn($user);

        $tweetRepository = $this->createStub(TweetRepositoryInterface::class);
        $tweetRepository->method('findById')->willReturn($tweet);

        $likeRepository = $this->createStub(LikeRepositoryInterface::class);
        $likeRepository->method('findByTweetAndUser')->willReturn(Like::create($tweet->id(), $user->id()));

        $handler = new LikeTweetHandler(
            $likeRepository,
            $tweetRepository,
            $userRepository,
            $this->createStub(EventPublisherInterface::class),
            $this->createStub(MetricsRegistryInterface::class),
        );

        $this->expectException(LikeAlreadyExistsException::class);

        $handler(new LikeTweetCommand(
            $tweet->id()->toString(),
            $user->id()->toString(),
        ));
    }
}
