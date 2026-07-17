<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\Messenger;

use App\Shared\Infrastructure\Metrics\FeedMetrics;
use App\Tweet\Domain\Event\TweetWasCreated;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Prometheus\Exception\MetricsRegistrationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Projects TweetWasCreated into the feed_items read model (eventual consistency).
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class FeedProjector
{
    public function __construct(
        private Connection $connection,
        private FeedMetrics $feedMetrics,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Exception
     * @throws \DateMalformedStringException
     * @throws MetricsRegistrationException
     */
    public function __invoke(TweetWasCreated $event): void
    {
        $tweetId = $event->tweetId->toString();
        $authorId = $event->authorId->toString();

        $tweet = $this->connection->fetchAssociative(
            'SELECT id, author_id, text, likes_count, created_at FROM tweets WHERE id = :id',
            ['id' => $tweetId],
        );

        if (false === $tweet) {
            $this->logger->warning('FeedProjector skipped: tweet not found', [
                'tweet_id' => $tweetId,
            ]);

            return;
        }

        $username = $this->connection->fetchOne(
            'SELECT username FROM users WHERE id = :id',
            ['id' => $authorId],
        );

        if (!\is_string($username) || '' === $username) {
            $this->logger->warning('FeedProjector skipped: author not found', [
                'tweet_id' => $tweetId,
                'author_id' => $authorId,
            ]);

            return;
        }

        $createdAt = $tweet['created_at'] instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($tweet['created_at'])
            : new \DateTimeImmutable((string) $tweet['created_at']);

        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO feed_items (id, tweet_id, author_id, author_username, text, likes_count, created_at)
                VALUES (:id, :tweet_id, :author_id, :author_username, :text, :likes_count, :created_at)
                ON CONFLICT (tweet_id) DO NOTHING
                SQL,
            [
                'id' => Uuid::v7()->toRfc4122(),
                'tweet_id' => (string) $tweet['id'],
                'author_id' => (string) $tweet['author_id'],
                'author_username' => $username,
                'text' => (string) $tweet['text'],
                'likes_count' => (int) $tweet['likes_count'],
                'created_at' => $createdAt,
            ],
            [
                'created_at' => Types::DATETIME_IMMUTABLE,
            ],
        );

        $lagSeconds = (float) new \DateTimeImmutable()->format('U.u') - (float) $createdAt->format('U.u');
        $this->feedMetrics->observeProjectionLag(max(0.0, $lagSeconds));
    }
}
