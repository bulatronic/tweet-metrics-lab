<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\Messenger;

use App\Tweet\Domain\Event\TweetWasLiked;
use App\Tweet\Domain\Event\TweetWasUnliked;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Updates likes_count on the write model (tweets) and feed projection after like events.
 */
final readonly class CounterUpdater
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @throws Exception
     */
    #[AsMessageHandler(bus: 'event.bus')]
    public function onLiked(TweetWasLiked $event): void
    {
        $this->adjustCounters($event->tweetId->toString(), 1);
    }

    /**
     * @throws Exception
     */
    #[AsMessageHandler(bus: 'event.bus')]
    public function onUnliked(TweetWasUnliked $event): void
    {
        $this->adjustCounters($event->tweetId->toString(), -1);
    }

    /**
     * @throws Exception
     */
    private function adjustCounters(string $tweetId, int $delta): void
    {
        $this->connection->executeStatement(
            'UPDATE tweets SET likes_count = GREATEST(0, likes_count + :delta) WHERE id = :tweet_id',
            [
                'delta' => $delta,
                'tweet_id' => $tweetId,
            ],
        );

        $this->connection->executeStatement(
            'UPDATE feed_items SET likes_count = GREATEST(0, likes_count + :delta) WHERE tweet_id = :tweet_id',
            [
                'delta' => $delta,
                'tweet_id' => $tweetId,
            ],
        );
    }
}
