<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Messenger;

use App\Search\Infrastructure\Elasticsearch\TweetsIndex;
use App\Shared\Infrastructure\Metrics\ElasticsearchMetrics;
use App\Tweet\Domain\Event\TweetWasCreated;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Prometheus\Exception\MetricsRegistrationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Indexes TweetWasCreated into Elasticsearch (eventual consistency, same async bus as FeedProjector).
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class TweetSearchIndexer
{
    public function __construct(
        private Client $elasticsearch,
        private Connection $connection,
        private ElasticsearchMetrics $elasticsearchMetrics,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     * @throws \DateMalformedStringException
     * @throws ClientResponseException
     * @throws ElasticsearchException
     * @throws ServerResponseException
     * @throws Exception
     * @throws MissingParameterException
     */
    public function __invoke(TweetWasCreated $event): void
    {
        $tweetId = $event->tweetId->toString();
        $authorId = $event->authorId->toString();

        $tweet = $this->connection->fetchAssociative(
            'SELECT id, author_id, text, created_at FROM tweets WHERE id = :id',
            ['id' => $tweetId],
        );

        if (false === $tweet) {
            $this->logger->warning('TweetSearchIndexer skipped: tweet not found', [
                'tweet_id' => $tweetId,
            ]);

            return;
        }

        $username = $this->connection->fetchOne(
            'SELECT username FROM users WHERE id = :id',
            ['id' => $authorId],
        );

        if (!\is_string($username) || '' === $username) {
            $this->logger->warning('TweetSearchIndexer skipped: author not found', [
                'tweet_id' => $tweetId,
                'author_id' => $authorId,
            ]);

            return;
        }

        $createdAt = $tweet['created_at'] instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($tweet['created_at'])
            : new \DateTimeImmutable((string) $tweet['created_at']);

        $startedAt = microtime(true);

        /** @var array<mixed> $document */
        $document = [
            'id' => (string) $tweet['id'],
            'text' => (string) $tweet['text'],
            'authorId' => (string) $tweet['author_id'],
            'authorUsername' => $username,
            'createdAt' => $createdAt->format(\DateTimeInterface::ATOM),
        ];

        try {
            $this->elasticsearch->index([
                'index' => TweetsIndex::NAME,
                'id' => (string) $tweet['id'],
                'body' => $document,
            ]);
        } catch (ElasticsearchException $exception) {
            $this->elasticsearchMetrics->incrementIndexErrors();
            $this->logger->error('TweetSearchIndexer failed to index tweet', [
                'tweet_id' => $tweetId,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            $this->elasticsearchMetrics->observeIndexDuration(microtime(true) - $startedAt);
        }
    }
}
