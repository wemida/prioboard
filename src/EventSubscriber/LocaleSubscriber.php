<?php

namespace App\EventSubscriber;

use App\Service\AppSettingsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private AppSettingsService $settingsService)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->setLocale($this->settingsService->getSettings()->getLanguage());
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }
}
