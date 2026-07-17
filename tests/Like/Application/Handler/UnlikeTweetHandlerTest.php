<?php

declare(strict_types=1);

namespace App\Tests\Like\Application\Handler;

use App\Like\Application\Command\UnlikeTweetCommand;
use App\Like\Application\Handler\UnlikeTweetHandler;
use App\Like\Domain\Entity\Like;
use App\Like\Domain\Exception\LikeNotFoundException;
use App\Like\Domain\Repository\LikeRepositoryInterface;
use App\Shared\Domain\EventPublisherInterface;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Shared\Domain\MetricsRegistryInterface;
use App\Tweet\Domain\Event\TweetWasUnliked;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

final class UnlikeTweetHandlerTest extends TestCase
{
    /**
     * @throws RandomException
     * @throws InvalidUuidException
     * @throws LikeNotFoundException
     */
    public function testRemovesLikeAndPublishesUnlikedEvent(): void
    {
        $tweetId = TweetId::generate();
        $userId = UserId::generate();
        $like = Like::create($tweetId, $userId);

        $likeRepository = $this->createMock(LikeRepositoryInterface::class);
        $likeRepository->method('findByTweetAndUser')->willReturn($like);
        $likeRepository
            ->expects($this->once())
            ->method('remove')
            ->with($like);

        $eventPublisher = $this->createMock(EventPublisherInterface::class);
        $eventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function (object $event) use ($tweetId, $userId): bool {
                return $event instanceof TweetWasUnliked
                    && $event->tweetId->equals($tweetId)
                    && $event->userId->equals($userId);
            }));

        $metrics = $this->createMock(MetricsRegistryInterface::class);
        $metrics->expects($this->once())->method('incrementUnlikes');

        $handler = new UnlikeTweetHandler($likeRepository, $eventPublisher, $metrics);

        $handler(new UnlikeTweetCommand(
            $tweetId->toString(),
            $userId->toString(),
        ));
    }

    /**
     * @throws RandomException
     * @throws InvalidUuidException
     */
    public function testThrowsWhenLikeMissing(): void
    {
        $likeRepository = $this->createStub(LikeRepositoryInterface::class);
        $likeRepository->method('findByTweetAndUser')->willReturn(null);

        $handler = new UnlikeTweetHandler(
            $likeRepository,
            $this->createStub(EventPublisherInterface::class),
            $this->createStub(MetricsRegistryInterface::class),
        );

        $this->expectException(LikeNotFoundException::class);

        $handler(new UnlikeTweetCommand(
            TweetId::generate()->toString(),
            UserId::generate()->toString(),
        ));
    }
}
