<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Diversworld\ContaoIssueServiceBundle\Application\License\{LicenseService, PremiumFeatureResolver};
use Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaEscalationService;
use Diversworld\ContaoIssueServiceBundle\Application\NotificationService;

final class SlaCron
{
    public function __construct(private readonly LicenseService $licenses, private readonly PremiumFeatureResolver $premium,
        private readonly SlaEscalationService $escalations, private readonly NotificationService $notifications, private readonly \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaWebhookDispatcher $webhooks) {}
    #[AsCronJob('hourly')]
    public function validate(): void
    {
        $status = $this->licenses->status();
        if (($status['claims']['mode'] ?? null) === 'online') $this->licenses->refresh();
    }
    #[AsCronJob('minutely')]
    public function calculate(): void
    {
        if (!$this->premium->enabled()) return;
        $this->escalations->escalate();
        $this->notifications->dispatchPending();
        $this->webhooks->dispatch();
    }
}
