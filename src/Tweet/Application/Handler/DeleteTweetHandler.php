<?php

declare(strict_types=1);

namespace App\Tweet\Application\Handler;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\Shared\Domain\TransactionManagerInterface;
use App\Tweet\Application\Command\DeleteTweetCommand;
use App\Tweet\Domain\Exception\TweetNotFoundException;
use App\Tweet\Domain\Repository\TweetRepositoryInterface;
use App\Tweet\Domain\ValueObject\TweetId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteTweetHandler
{
    public function __construct(
        private TweetRepositoryInterface $tweetRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    /**
     * @throws InvalidUuidException
     * @throws TweetNotFoundException
     */
    public function __invoke(DeleteTweetCommand $command): void
    {
        $tweetId = TweetId::fromString($command->tweetId);
        $tweet = $this->tweetRepository->findById($tweetId);

        if (null === $tweet) {
            throw new TweetNotFoundException($tweetId);
        }

        $this->transactionManager->transactional(function () use ($tweet): void {
            $this->tweetRepository->delete($tweet);
        });
    }
}
