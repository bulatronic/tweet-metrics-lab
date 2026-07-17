<?php

declare(strict_types=1);

namespace App\Like\Domain\Repository;

use App\Like\Domain\Entity\Like;
use App\Like\Domain\ValueObject\LikeId;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;

interface LikeRepositoryInterface
{
    public function save(Like $like): void;

    public function remove(Like $like): void;

    public function findById(LikeId $id): ?Like;

    public function findByTweetAndUser(TweetId $tweetId, UserId $userId): ?Like;
}
