<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\Username;
use Random\RandomException;

final readonly class User
{
    private function __construct(
        private UserId $id,
        private Email $email,
        private PasswordHash $passwordHash,
        private Username $username,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @throws RandomException
     * @throws InvalidUuidException
     */
    public static function register(
        Email $email,
        PasswordHash $passwordHash,
        Username $username,
    ): self {
        return new self(
            UserId::generate(),
            $email,
            $passwordHash,
            $username,
            new \DateTimeImmutable(),
        );
    }

    public static function reconstitute(
        UserId $id,
        Email $email,
        PasswordHash $passwordHash,
        Username $username,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $email, $passwordHash, $username, $createdAt);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function username(): Username
    {
        return $this->username;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
