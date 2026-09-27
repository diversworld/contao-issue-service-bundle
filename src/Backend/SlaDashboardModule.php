<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Backend;

use Contao\{BackendModule, BackendUser, System, Input};
use Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaReportExporter;
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
        $month = Input::get('month') ?: date('Y-m');
        if (!is_string($month)) throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Ungültiger Monat.');
        $router = $container->get('router');
        if (!$router instanceof \Symfony\Component\Routing\Generator\UrlGeneratorInterface) throw new \LogicException('Router unavailable.');
        try { [$from, $until] = SlaReportExporter::month($month); }
        catch (\InvalidArgumentException $error) { throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException($error->getMessage(), $error); }
        $report = $reports->report(null, $from, $until);
        $live = $reports->report();
        $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $metrics = $report['metrics'];
        $html = '<div class="tl_listing_container"><h2>SLA-Dashboard und Berichte</h2><p>Lizenz: '.$escape($status['state']).'</p><form method="get"><input type="hidden" name="do" value="issue_service_sla_dashboard"><label>Berichtsmonat <input type="month" name="month" value="'.$escape($month).'"></label> <button type="submit">Anzeigen</button></form><p>';
        foreach (['pdf' => 'PDF', 'xlsx' => 'Excel', 'csv' => 'CSV'] as $format => $label) {
            $url = $router->generate('issue_sla_export', ['format' => $format, 'month' => $month]);
            $html .= '<a href="'.$escape($url).'">'.$label.'</a> · ';
        }
        $html .= '</p><h3>Monatskennzahlen</h3><dl>';
        foreach (['total' => 'Tickets mit SLA', 'breached' => 'Tickets mit Verletzungen', 'completed' => 'Abgeschlossene Tickets', 'completed_on_time' => 'Fristgerecht abgeschlossen', 'compliance_percent' => 'Erfüllungsquote abgeschlossener Tickets (%)'] as $key => $label) $html .= '<dt>'.$label.'</dt><dd>'.$escape($metrics[$key] ?? '–').'</dd>';
        foreach (['response' => 'Durchschnittliche Reaktionszeit', 'resolve' => 'Durchschnittliche Lösungszeit'] as $kind => $label) {
            $seconds = $metrics['average_'.$kind.'_seconds'];
            $html .= '<dt>'.$label.'</dt><dd>'.($seconds === null ? '–' : $escape(round($seconds / 3600, 2)).' Supportstunden').' ('.$escape($metrics[$kind.'_samples']).' Tickets)</dd>';
        }
        $html .= '</dl><h3>Aktueller Status (alle Monate)</h3><p>Kritische Tickets: '.$escape($live['metrics']['critical']).' · Offene Eskalationen: '.$escape($live['metrics']['open_escalations']).'</p>';
        foreach (['critical_tickets' => 'Kritische Tickets', 'open_escalations' => 'Offene Eskalationen'] as $key => $label) {
            $html .= '<h3>'.$label.'</h3><table class="tl_listing"><thead><tr><th>Ticket</th><th>Betreff</th><th>Priorität / Eskalation</th></tr></thead><tbody>';
            foreach ($live[$key] as $row) $html .= '<tr><td>'.$escape($row['ticket_number']).'</td><td>'.$escape($row['title']).'</td><td>'.$escape($row['priority'] ?? $row['event_type']).'</td></tr>';
            $html .= '</tbody></table>';
        }
        $html .= '<h3>Top-Kategorien im Monat</h3><table class="tl_listing"><thead><tr><th>Kategorie</th><th>Tickets</th></tr></thead><tbody>';
        foreach ($report['categories'] as $row) $html .= '<tr><td>'.$escape($row['name']).'</td><td>'.$escape($row['tickets']).'</td></tr>';
        $html .= '</tbody></table><h3>Agentenstatistik im Monat</h3><table class="tl_listing"><thead><tr><th>Agent</th><th>Tickets</th><th>Verletzungen</th><th>Abgeschlossen</th><th>Ø Reaktion (h)</th><th>Ø Lösung (h)</th></tr></thead><tbody>';
        foreach ($report['agents'] as $row) {
            $html .= '<tr>';
            foreach (['name','tickets','breached','completed'] as $key) $html .= '<td>'.$escape($row[$key]).'</td>';
            foreach (['response','resolve'] as $kind) $html .= '<td>'.$escape($row['average_'.$kind.'_seconds'] === null ? '–' : round($row['average_'.$kind.'_seconds'] / 3600, 2)).'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table><h3>Verletzungsmonitor</h3><p>Letzte 200 betroffene Tickets. Aktualisierung durch den SLA-Scheduler.</p><table class="tl_listing"><thead><tr><th>Ticket</th><th>Betreff</th><th>Status</th><th>Verletzt seit</th></tr></thead><tbody>';
        foreach ($report['violations'] as $row) $html .= '<tr><td>'.$escape($row['ticket_number']).'</td><td>'.$escape($row['title']).'</td><td>'.$escape($row['sla_state']).'</td><td>'.$escape(date('d.m.Y H:i', (int) $row['sla_breached_at'])).'</td></tr>';
        return $html.'</tbody></table></div>';
    }
    protected function compile(): void {}
}
