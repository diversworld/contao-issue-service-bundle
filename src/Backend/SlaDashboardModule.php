<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Backend;

use Contao\{BackendModule, BackendUser, System};
use Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaReportingService;
use Diversworld\ContaoIssueServiceBundle\Application\License\LicenseService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SlaDashboardModule extends BackendModule
{
    public function generate(): string
    {
        $user = BackendUser::getInstance();
        // Reports contain cross-service data and are deliberately administrator-only.
        if (!$user instanceof BackendUser || !$user->isAdmin) throw new AccessDeniedHttpException('SLA-Berichte sind Administratoren vorbehalten.');
        $container = System::getContainer();
        $licenses = $container->get(LicenseService::class);
        $reports = $container->get(SlaReportingService::class);
        if (!$licenses instanceof LicenseService || !$reports instanceof SlaReportingService) throw new \LogicException('SLA services unavailable.');
        $status = $licenses->status();
        if (!$status['enabled']) return '<div class="tl_listing_container"><h2>SLA</h2><p class="tl_error">Eine gültige SLA-Lizenz ist erforderlich. Bitte die Lizenzverwaltung öffnen.</p></div>';
        $report = $reports->report();
        $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $metrics = $report['metrics'];
        $html = '<div class="tl_listing_container"><h2>SLA-Dashboard und Berichte</h2><p>Lizenz: '.$escape($status['state']).'</p><dl>';
        foreach (['total' => 'Tickets mit SLA', 'breached' => 'Tickets mit Verletzungen', 'completed' => 'Abgeschlossene Tickets', 'completed_on_time' => 'Fristgerecht abgeschlossen', 'compliance_percent' => 'Erfüllungsquote abgeschlossener Tickets (%)'] as $key => $label) $html .= '<dt>'.$label.'</dt><dd>'.$escape($metrics[$key] ?? '–').'</dd>';
        $html .= '</dl><h3>Verletzungsmonitor</h3><p>Letzte 200 betroffene Tickets. Aktualisierung durch den SLA-Scheduler.</p><table class="tl_listing"><thead><tr><th>Ticket</th><th>Betreff</th><th>Status</th><th>Verletzt seit</th></tr></thead><tbody>';
        foreach ($report['violations'] as $row) $html .= '<tr><td>'.$escape($row['ticket_number']).'</td><td>'.$escape($row['title']).'</td><td>'.$escape($row['sla_state']).'</td><td>'.$escape(date('d.m.Y H:i', (int) $row['sla_breached_at'])).'</td></tr>';
        return $html.'</tbody></table></div>';
    }
    protected function compile(): void {}
}
