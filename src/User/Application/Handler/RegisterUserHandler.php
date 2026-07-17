<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\InvalidUsernameException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Service\PasswordHasherInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\Username;
use Random\RandomException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws RandomException
     * @throws InvalidUsernameException
     * @throws UserAlreadyExistsException
     * @throws InvalidUuidException
     * @throws InvalidEmailException
     */
    public function __invoke(RegisterUserCommand $command): string
    {
        $email = Email::fromString($command->email);
        $username = Username::fromString($command->username);

        if (null !== $this->userRepository->findByEmail($email)) {
            throw new UserAlreadyExistsException($email->toString());
        }

        if (null !== $this->userRepository->findByUsername($username)) {
            throw new UserAlreadyExistsException($username->toString());
        }

        $passwordHash = $this->passwordHasher->hash($command->plainPassword);
        $user = User::register($email, $passwordHash, $username);

        $this->userRepository->save($user);

        return $user->id()->toString();
    }
}
