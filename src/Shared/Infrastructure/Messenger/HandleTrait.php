<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

trait HandleTrait
{
    /**
     * @throws ExceptionInterface
     */
    private function handle(MessageBusInterface $bus, object $message): mixed
    {
        $envelope = $bus->dispatch($message);
        /** @var HandledStamp|null $stamp */
        $stamp = $envelope->last(HandledStamp::class);

        return $stamp?->getResult();
    }
}
