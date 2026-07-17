<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use ApiKit\Exception\ApiException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * IP-based rate limit for POST /api/login (5 requests / minute).
 */
final readonly class LoginRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.login_ip')]
        private RateLimiterFactory $loginIpLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Before firewall authentication
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('POST' !== $request->getMethod() || '/api/login' !== $request->getPathInfo()) {
            return;
        }

        $limiter = $this->loginIpLimiter->create((string) $request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            throw new ApiException(429, 'Too many login attempts. Try again later.', ['reason' => 'rate_limit_exceeded']);
        }
    }
}
