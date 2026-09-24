<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getRequest()->hasPreviousSession()) {
            return;
        }
        $this->apply($event, (string) $event->getRequest()->getSession()->get('_locale', 'en'));
    }

    public function onAuthenticatedRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }
        $this->apply($event, $user->getLocale());
        $event->getRequest()->getSession()->set('_locale', $event->getRequest()->getLocale());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 20],
                ['onAuthenticatedRequest', 7],
            ],
        ];
    }

    private function apply(RequestEvent $event, string $locale): void
    {
        if (!in_array($locale, ['en', 'ru'], true)) {
            $locale = 'en';
        }
        $event->getRequest()->setLocale($locale);
        $this->translator->setLocale($locale);
    }
}
