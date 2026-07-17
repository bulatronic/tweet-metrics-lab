<?php

declare(strict_types=1);

namespace App\Tweet\Application\Command;

final readonly class CreateTweetCommand
{
    public function __construct(
        public string $authorId,
        public string $text,
    ) {
    }
}
