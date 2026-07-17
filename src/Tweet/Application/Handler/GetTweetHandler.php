<?php

declare(strict_types=1);

namespace App\Tweet\Application\Handler;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Application\DTO\TweetDTO;
use App\Tweet\Application\Query\GetTweetQuery;
use App\Tweet\Domain\Exception\TweetNotFoundException;
use App\Tweet\Domain\Repository\TweetRepositoryInterface;
use App\Tweet\Domain\ValueObject\TweetId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTweetHandler
{
    public function __construct(
        private TweetRepositoryInterface $tweetRepository,
    ) {
    }

    /**
     * @throws InvalidUuidException
     * @throws TweetNotFoundException
     */
    public function __invoke(GetTweetQuery $query): TweetDTO
    {
        $tweetId = TweetId::fromString($query->tweetId);
        $tweet = $this->tweetRepository->findById($tweetId);

        if (null === $tweet) {
            throw new TweetNotFoundException($tweetId);
        }

        return new TweetDTO(
            id: $tweet->id()->toString(),
            authorId: $tweet->authorId()->toString(),
            text: $tweet->text()->toString(),
            likesCount: $tweet->likesCount(),
            createdAt: $tweet->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
