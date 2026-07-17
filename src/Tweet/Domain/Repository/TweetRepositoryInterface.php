<?php

declare(strict_types=1);

namespace App\Tweet\Domain\Repository;

use App\Tweet\Domain\Entity\Tweet;
use App\Tweet\Domain\ValueObject\TweetId;

interface TweetRepositoryInterface
{
    public function save(Tweet $tweet): void;

    public function delete(Tweet $tweet): void;

    public function findById(TweetId $id): ?Tweet;
}
