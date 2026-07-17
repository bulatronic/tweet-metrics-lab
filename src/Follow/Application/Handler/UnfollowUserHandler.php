<?php

declare(strict_types=1);

namespace App\Follow\Application\Handler;

use App\Follow\Application\Command\UnfollowUserCommand;
use App\Follow\Domain\Event\UserWasUnfollowed;
use App\Follow\Domain\Exception\FollowNotFoundException;
use App\Follow\Domain\Repository\FollowRepositoryInterface;
use App\Shared\Domain\EventPublisherInterface;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Shared\Domain\TransactionManagerInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UnfollowUserHandler
{
    public function __construct(
        private FollowRepositoryInterface $followRepository,
        private EventPublisherInterface $eventPublisher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    /**
     * @throws InvalidUuidException
     * @throws FollowNotFoundException
     */
    public function __invoke(UnfollowUserCommand $command): void
    {
        $followerId = UserId::fromString($command->followerId);
        $followeeId = UserId::fromString($command->followeeId);

        $follow = $this->followRepository->findByFollowerAndFollowee($followerId, $followeeId);
        if (null === $follow) {
            throw new FollowNotFoundException($followerId, $followeeId);
        }

        $occurredAt = new \DateTimeImmutable();

        $this->transactionManager->transactional(function () use ($follow, $followerId, $followeeId, $occurredAt): void {
            $this->followRepository->remove($follow);
            $this->eventPublisher->publish(new UserWasUnfollowed($followerId, $followeeId, $occurredAt));
        });
    }
}
