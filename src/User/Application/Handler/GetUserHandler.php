<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\User\Application\DTO\UserDTO;
use App\User\Application\Query\GetUserQuery;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws InvalidUuidException
     */
    public function __invoke(GetUserQuery $query): UserDTO
    {
        $userId = UserId::fromString($query->userId);
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw new UserNotFoundException($userId);
        }

        return new UserDTO(
            id: $user->id()->toString(),
            email: $user->email()->toString(),
            username: $user->username()->toString(),
            createdAt: $user->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
