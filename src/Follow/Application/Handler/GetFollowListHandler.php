<?php

declare(strict_types=1);

namespace App\Follow\Application\Handler;

use App\Follow\Application\DTO\FollowListDTO;
use App\Follow\Application\DTO\FollowUserDTO;
use App\Follow\Application\Query\GetFollowersQuery;
use App\Follow\Application\Query\GetFollowingQuery;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class GetFollowListHandler
{
    private const int MAX_LIMIT = 50;

    public function __construct(
        private Connection $connection,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    #[AsMessageHandler(bus: 'query.bus')]
    public function followers(GetFollowersQuery $query): FollowListDTO
    {
        return $this->fetchList(
            userId: $query->userId,
            cursor: $query->cursor,
            limit: $query->limit,
            joinUserColumn: 'follower_id',
            filterColumn: 'followee_id',
        );
    }

    /**
     * @throws UserNotFoundException
     */
    #[AsMessageHandler(bus: 'query.bus')]
    public function following(GetFollowingQuery $query): FollowListDTO
    {
        return $this->fetchList(
            userId: $query->userId,
            cursor: $query->cursor,
            limit: $query->limit,
            joinUserColumn: 'followee_id',
            filterColumn: 'follower_id',
        );
    }

    /**
     * @throws \DateMalformedStringException
     * @throws UserNotFoundException
     * @throws InvalidUuidException
     * @throws Exception
     * @throws \JsonException
     */
    private function fetchList(
        string $userId,
        ?string $cursor,
        int $limit,
        string $joinUserColumn,
        string $filterColumn,
    ): FollowListDTO {
        $id = UserId::fromString($userId);
        if (null === $this->userRepository->findById($id)) {
            throw new UserNotFoundException($id);
        }

        $limit = max(1, min(self::MAX_LIMIT, $limit));

        $sql = <<<SQL
            SELECT u.id, u.username, f.created_at
            FROM follows f
            INNER JOIN users u ON u.id = f.{$joinUserColumn}
            WHERE f.{$filterColumn} = :user_id
            SQL;

        $params = ['user_id' => $id->toString()];
        $types = ['user_id' => ParameterType::STRING];

        if (null !== $cursor && '' !== $cursor) {
            [$cursorCreatedAt, $cursorId] = $this->decodeCursor($cursor);
            $sql .= ' AND (f.created_at, u.id) < (:cursor_created_at, :cursor_id)';
            $params['cursor_created_at'] = $cursorCreatedAt;
            $params['cursor_id'] = $cursorId;
            $types['cursor_created_at'] = Types::DATETIME_IMMUTABLE;
            $types['cursor_id'] = ParameterType::STRING;
        }

        $sql .= ' ORDER BY f.created_at DESC, u.id DESC LIMIT :limit';
        $params['limit'] = $limit;
        $types['limit'] = ParameterType::INTEGER;

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);

        $items = [];
        foreach ($rows as $row) {
            $createdAt = $this->normalizeDateTime($row['created_at']);
            $items[] = new FollowUserDTO(
                id: (string) $row['id'],
                username: (string) $row['username'],
                followedAt: $createdAt->format(\DateTimeInterface::ATOM),
            );
        }

        $nextCursor = null;
        if (\count($items) === $limit) {
            $last = $items[array_key_last($items)];
            $nextCursor = $this->encodeCursor(
                new \DateTimeImmutable($last->followedAt),
                $last->id,
            );
        }

        return new FollowListDTO($items, $nextCursor);
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: string}
     */
    private function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);
        if (false === $decoded) {
            throw new \InvalidArgumentException('Invalid follow cursor.');
        }

        $payload = json_decode($decoded, true);
        if (!\is_array($payload) || !isset($payload['createdAt'], $payload['id'])) {
            throw new \InvalidArgumentException('Invalid follow cursor payload.');
        }

        return [
            new \DateTimeImmutable((string) $payload['createdAt']),
            (string) $payload['id'],
        ];
    }

    private function encodeCursor(\DateTimeImmutable $createdAt, string $id): string
    {
        return base64_encode(json_encode([
            'createdAt' => $createdAt->format(\DateTimeInterface::ATOM),
            'id' => $id,
        ], \JSON_THROW_ON_ERROR));
    }

    private function normalizeDateTime(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        return new \DateTimeImmutable((string) $value);
    }
}
