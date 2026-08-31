<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use ApiKit\Exception\ApiException;
use App\Follow\Domain\Exception\CannotFollowYourselfException;
use App\Follow\Domain\Exception\FollowAlreadyExistsException;
use App\Follow\Domain\Exception\FollowNotFoundException;
use App\Like\Domain\Exception\LikeAlreadyExistsException;
use App\Like\Domain\Exception\LikeNotFoundException;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Domain\Exception\CannotDecrementLikesException;
use App\Tweet\Domain\Exception\TweetNotFoundException;
use App\Tweet\Domain\Exception\TweetTextTooLongException;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\InvalidUsernameException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Maps DomainException to ApiException so api-kit ExceptionListener can format the response.
 */
final class DomainExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // Before api-kit ExceptionListener (priority 0)
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $domainException = $this->extractDomainException($event->getThrowable());
        if (null === $domainException) {
            return;
        }

        $event->setThrowable($this->toApiException($domainException));
    }

    /**
     * Messenger wraps handler exceptions into HandlerFailedException, so unwrap before mapping.
     */
    private function extractDomainException(\Throwable $throwable): ?DomainException
    {
        while (null !== $throwable) {
            if ($throwable instanceof DomainException) {
                return $throwable;
            }

            if ($throwable instanceof HandlerFailedException) {
                $wrapped = $throwable->getWrappedExceptions();
                $throwable = [] === $wrapped ? $throwable->getPrevious() : reset($wrapped);

                continue;
            }

            $throwable = $throwable->getPrevious();
        }

        return null;
    }

    private function toApiException(DomainException $exception): ApiException
    {
        return match (true) {
            $exception instanceof UserAlreadyExistsException,
            $exception instanceof LikeAlreadyExistsException,
            $exception instanceof FollowAlreadyExistsException => new ApiException(
                409,
                $exception->getMessage(),
                ['reason' => $this->reason($exception)],
                $exception,
            ),
            $exception instanceof UserNotFoundException,
            $exception instanceof TweetNotFoundException,
            $exception instanceof LikeNotFoundException,
            $exception instanceof FollowNotFoundException => new ApiException(
                404,
                $exception->getMessage(),
                ['reason' => $this->reason($exception)],
                $exception,
            ),
            $exception instanceof CannotFollowYourselfException,
            $exception instanceof TweetTextTooLongException,
            $exception instanceof InvalidEmailException,
            $exception instanceof InvalidUsernameException,
            $exception instanceof InvalidUuidException,
            $exception instanceof CannotDecrementLikesException => new ApiException(
                422,
                $exception->getMessage(),
                ['reason' => $this->reason($exception)],
                $exception,
            ),
            default => new ApiException(
                422,
                $exception->getMessage(),
                ['reason' => 'domain_error'],
                $exception,
            ),
        };
    }

    private function reason(DomainException $exception): string
    {
        return match (true) {
            $exception instanceof UserAlreadyExistsException => 'user_already_exists',
            $exception instanceof LikeAlreadyExistsException => 'like_already_exists',
            $exception instanceof FollowAlreadyExistsException => 'follow_already_exists',
            $exception instanceof UserNotFoundException => 'user_not_found',
            $exception instanceof TweetNotFoundException => 'tweet_not_found',
            $exception instanceof LikeNotFoundException => 'like_not_found',
            $exception instanceof FollowNotFoundException => 'follow_not_found',
            $exception instanceof CannotFollowYourselfException => 'cannot_follow_yourself',
            $exception instanceof TweetTextTooLongException => 'tweet_text_too_long',
            $exception instanceof InvalidEmailException => 'invalid_email',
            $exception instanceof InvalidUsernameException => 'invalid_username',
            $exception instanceof InvalidUuidException => 'invalid_uuid',
            $exception instanceof CannotDecrementLikesException => 'cannot_decrement_likes',
            default => 'domain_error',
        };
    }
}
