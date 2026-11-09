<?php

namespace UnifolioBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/** Transforme les erreurs métier UniFolio en réponse 422 { message, erreurs }. */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class UnifolioValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof UnifolioValidationException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'message' => $exception->getMessage(),
            'erreurs' => $exception->getErreurs(),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
