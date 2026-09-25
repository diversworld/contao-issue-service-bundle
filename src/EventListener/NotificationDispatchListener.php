<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Diversworld\ContaoIssueServiceBundle\Application\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class NotificationDispatchListener
{
    public function __construct(private readonly NotificationService $notifications) {}

    #[AsEventListener(event: KernelEvents::TERMINATE)]
    public function afterResponse(TerminateEvent $event): void
    {
        if ($event->isMainRequest() && $this->notifications->hasQueuedNotifications()) {
            $this->notifications->dispatchPending();
        }
    }

    #[AsCronJob('minutely')]
    public function retry(): void
    {
        $this->notifications->dispatchPending();
    }
}
