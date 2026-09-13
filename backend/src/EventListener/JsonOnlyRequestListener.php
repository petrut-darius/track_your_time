<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class JsonOnlyRequestListener
{
    private const ALLOWED_PREFIXES = ['application/json', 'multipart/form-data'];

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
    public function onRequestEvent(RequestEvent $event): void
    {
        /*
        $contentType = $event->getRequest()->headers->get('Content-Type', '');

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($contentType, $prefix)) {
                return;
            }
        }

        throw new UnsupportedMediaTypeHttpException(
            sprintf('Unsupported Content-Type "%s". Expected application/json or multipart/form-data.', $contentType)
        );
        */
    }
}
