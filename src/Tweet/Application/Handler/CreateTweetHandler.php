<?php

declare(strict_types=1);

namespace App\Tweet\Application\Handler;

use App\Shared\Domain\EventPublisherInterface;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Application\Command\CreateTweetCommand;
use App\Tweet\Domain\Entity\Tweet;
use App\Tweet\Domain\Event\TweetWasCreated;
use App\Tweet\Domain\Exception\TweetTextTooLongException;
use App\Tweet\Domain\Repository\TweetRepositoryInterface;
use App\Tweet\Domain\ValueObject\TweetText;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Random\RandomException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateTweetHandler
{
    public function __construct(
        private TweetRepositoryInterface $tweetRepository,
        private UserRepositoryInterface $userRepository,
        private EventPublisherInterface $eventPublisher,
    ) {
    }

    /**
     * @throws RandomException
     * @throws UserNotFoundException
     * @throws TweetTextTooLongException
     * @throws InvalidUuidException
     */
    public function __invoke(CreateTweetCommand $command): string
    {
        $authorId = UserId::fromString($command->authorId);

        if (null === $this->userRepository->findById($authorId)) {
            throw new UserNotFoundException($authorId);
        }

        $tweet = Tweet::create($authorId, TweetText::fromString($command->text));

        $this->tweetRepository->save($tweet);
        $this->eventPublisher->publish(new TweetWasCreated(
            $tweet->id(),
            $tweet->authorId(),
            $tweet->createdAt(),
        ));

        return $tweet->id()->toString();
    }
}
