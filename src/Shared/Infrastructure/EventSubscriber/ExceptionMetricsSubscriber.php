<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Shared\Infrastructure\Exception\ThrowableChain;
use App\Shared\Infrastructure\Metrics\ExceptionMetrics;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Counts exceptions by their root cause class, feeding the "Exception Rate by Class" panel.
 *
 * The class is captured on kernel.exception, but counted on kernel.response: security and
 * api-kit listeners decide the final status code after the exception is dispatched.
 */
final readonly class ExceptionMetricsSubscriber implements EventSubscriberInterface
{
    private const string REQUEST_ATTRIBUTE = '_exception_metrics_class';

    public function __construct(
        private ExceptionMetrics $exceptionMetrics,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 5],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $rootCause = ThrowableChain::rootCause($event->getThrowable());

        $event->getRequest()->attributes->set(self::REQUEST_ATTRIBUTE, $this->shortClassName($rootCause));
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $exceptionClass = $event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE);
        if (!\is_string($exceptionClass)) {
            return;
        }

        $this->exceptionMetrics->increment($exceptionClass, $event->getResponse()->getStatusCode());
    }

    /**
     * Short name keeps the Grafana legend readable; class names are unique enough here.
     */
    private function shortClassName(\Throwable $throwable): string
    {
        $parts = explode('\\', $throwable::class);

        return end($parts);
    }
}
