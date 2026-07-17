<?php

declare(strict_types=1);

namespace App\Follow\Application\Handler;

use App\Follow\Application\Command\FollowUserCommand;
use App\Follow\Domain\Entity\Follow;
use App\Follow\Domain\Event\UserWasFollowed;
use App\Follow\Domain\Exception\CannotFollowYourselfException;
use App\Follow\Domain\Exception\FollowAlreadyExistsException;
use App\Follow\Domain\Repository\FollowRepositoryInterface;
use App\Shared\Domain\EventPublisherInterface;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Shared\Domain\MetricsRegistryInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Random\RandomException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class FollowUserHandler
{
    public function __construct(
        private FollowRepositoryInterface $followRepository,
        private UserRepositoryInterface $userRepository,
        private EventPublisherInterface $eventPublisher,
        private MetricsRegistryInterface $metricsRegistry,
    ) {
    }

    /**
     * @throws CannotFollowYourselfException
     * @throws RandomException
     * @throws UserNotFoundException
     * @throws FollowAlreadyExistsException
     * @throws InvalidUuidException
     */
    public function __invoke(FollowUserCommand $command): void
    {
        $followerId = UserId::fromString($command->followerId);
        $followeeId = UserId::fromString($command->followeeId);

        if (null === $this->userRepository->findById($followerId)) {
            throw new UserNotFoundException($followerId);
        }

        if (null === $this->userRepository->findById($followeeId)) {
            throw new UserNotFoundException($followeeId);
        }

        if (null !== $this->followRepository->findByFollowerAndFollowee($followerId, $followeeId)) {
            throw new FollowAlreadyExistsException($followerId, $followeeId);
        }

        $follow = Follow::create($followerId, $followeeId);

        $this->followRepository->save($follow);
        $this->eventPublisher->publish(new UserWasFollowed(
            $follow->followerId(),
            $follow->followeeId(),
            $follow->createdAt(),
        ));
        $this->metricsRegistry->incrementFollows();
    }
}
