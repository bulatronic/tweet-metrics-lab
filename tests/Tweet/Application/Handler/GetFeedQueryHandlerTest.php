<?php

declare(strict_types=1);

namespace App\Tests\Tweet\Application\Handler;

use App\Tweet\Application\DTO\FeedPageDTO;
use App\Tweet\Application\Handler\GetFeedQueryHandler;
use App\Tweet\Application\Query\GetFeedQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GetFeedQueryHandlerTest extends TestCase
{
    private Connection&MockObject $connection;
    private CacheInterface&MockObject $feedCache;
    private GetFeedQueryHandler $handler;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->feedCache = $this->createMock(CacheInterface::class);
        $this->handler = new GetFeedQueryHandler($this->connection, $this->feedCache);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testReadsFeedViaDbalAndCachesResult(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-17T12:00:00+00:00');

        $this->feedCache
            ->expects($this->once())
            ->method('get')
            ->with('feed_start_20', $this->callback(static fn (mixed $value): bool => \is_callable($value)))
            ->willReturnCallback(function (string $key, callable $callback): FeedPageDTO {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())->method('expiresAfter')->with(30);

                return $callback($item);
            });

        $this->connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => '01900000-0000-7000-8000-000000000001',
                    'tweet_id' => '01900000-0000-7000-8000-000000000002',
                    'author_id' => '01900000-0000-7000-8000-000000000003',
                    'author_username' => 'alice',
                    'text' => 'hello feed',
                    'likes_count' => 3,
                    'created_at' => $createdAt,
                ],
            ]);

        $page = ($this->handler)(new GetFeedQuery());

        self::assertCount(1, $page->items);
        self::assertSame('hello feed', $page->items[0]->text);
        self::assertSame(3, $page->items[0]->likesCount);
        self::assertSame('alice', $page->items[0]->authorUsername);
        self::assertNull($page->nextCursor);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBuildsNextCursorWhenPageIsFull(): void
    {
        $this->feedCache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback): FeedPageDTO {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())->method('expiresAfter')->with(30);

                return $callback($item);
            });

        $this->connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => '01900000-0000-7000-8000-000000000001',
                    'tweet_id' => '01900000-0000-7000-8000-000000000002',
                    'author_id' => '01900000-0000-7000-8000-000000000003',
                    'author_username' => 'alice',
                    'text' => 'one',
                    'likes_count' => 0,
                    'created_at' => new \DateTimeImmutable('2026-07-17T12:00:00+00:00'),
                ],
            ]);

        $page = ($this->handler)(new GetFeedQuery(limit: 1));

        self::assertNotNull($page->nextCursor);
        $decoded = json_decode(base64_decode($page->nextCursor, true) ?: '', true);
        self::assertIsArray($decoded);
        self::assertSame('01900000-0000-7000-8000-000000000001', $decoded['id']);
    }
}
