<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Backend;

use Contao\BackendModule;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoIssueServiceBundle\Application\SettingsService;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('contao.backend_module', ['module' => 'issue_service_dashboard'])]
final class IssueDashboardModule extends BackendModule
{
    private SettingsService $settings;
    private Connection $connection;

    public function __construct(?DataContainer $dc = null)
    {
        parent::__construct($dc);

        $container = System::getContainer();
        $this->settings = $container->get(SettingsService::class);
        $this->connection = $container->get('database_connection');
    }

    public function generate(): string
    {
        $message = $this->settings->isComplete()
            ? '<p class="tl_confirm">Die Pflichtkonfiguration ist vollstaendig.</p>'
            : '<p class="tl_error">Die Pflichtkonfiguration ist unvollstaendig.</p>';

        return '<div class="tl_listing_container">'
            .'<h2>Issue &amp; Service Management</h2>'
            .$message
            .$this->renderMetrics()
            .$this->renderStatusTable()
            .$this->renderLatestIssues()
            .'</div>';
    }

    private function renderMetrics(): string
    {
        $openIssues = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_issue i JOIN tl_issue_status st ON st.id = i.status_id WHERE i.deleted_at IS NULL AND st.is_closed = 0');
        $allIssues = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_issue WHERE deleted_at IS NULL');
        $services = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_issue_service WHERE published = 1');
        $transitions = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_issue_transition WHERE published = 1');

        return '<div class="tl_content_right" style="float:none;margin:0 0 18px 0">'
            .$this->metric('Offene Issues', $openIssues)
            .$this->metric('Issues gesamt', $allIssues)
            .$this->metric('Aktive Services', $services)
            .$this->metric('Workflow-Regeln', $transitions)
            .'</div>';
    }

    private function metric(string $label, int $value): string
    {
        return sprintf(
            '<div style="display:inline-block;min-width:150px;margin:0 12px 12px 0;padding:12px;border:1px solid #dddddd;background:#dddddd; color: #333333;">
                <strong style="display:block;font-size:22px">%d</strong><span>%s</span></div>',
            $value,
            StringUtil::specialchars($label),
        );
    }

    private function renderStatusTable(): string
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT st.title, st.color, COUNT(i.id) issue_count FROM tl_issue_status st LEFT JOIN tl_issue i ON i.status_id = st.id AND i.deleted_at IS NULL WHERE st.published = 1 GROUP BY st.id, st.title, st.color ORDER BY st.sort_order',
        );

        if ([] === $rows) {
            return '<p class="tl_info">Es sind noch keine Workflow-Status angelegt.</p>';
        }

        $html = '<h3>Statusübersicht</h3>
        <table class="tl_listing showColumns"><thead><tr><th>Status</th><th>Issues</th></tr></thead><tbody>';

        foreach ($rows as $index => $row) {
            $color = $row['color'] ? '<span style="display:inline-block;width:12px;height:12px;margin-right:6px;border:1px solid #999;background:#'.StringUtil::specialchars((string) $row['color']).'"></span>' : '';
            $html .= sprintf(
                '<tr class="%s"><td>%s%s</td><td>%d</td></tr>',
                0 === $index % 2 ? 'even' : 'odd',
                $color,
                StringUtil::specialchars((string) $row['title']),
                (int) $row['issue_count'],
            );
        }

        return $html.'</tbody></table>';
    }

    private function renderLatestIssues(): string
    {
        $issues = $this->connection->fetchAllAssociative(
            'SELECT i.id, i.ticket_number, i.title, i.priority, i.last_public_activity_at, s.title service_title, st.title status_title FROM tl_issue i JOIN tl_issue_service s ON s.id = i.service_id JOIN tl_issue_status st ON st.id = i.status_id WHERE i.deleted_at IS NULL ORDER BY i.last_public_activity_at DESC, i.id DESC LIMIT 10',
        );

        if ([] === $issues) {
            return '<p class="tl_info">Es sind noch keine Issues vorhanden.</p>';
        }

        $html = '<h3>Letzte Issues</h3><table class="tl_listing showColumns"><thead><tr><th>Ticket</th><th>Titel</th><th>Service</th><th>Status</th><th>Priorität</th><th>Aktualisiert</th></tr></thead><tbody>';

        foreach ($issues as $index => $issue) {
            $container = System::getContainer();
            $href = $container->get('router')->generate('contao_backend', [
                'do' => 'issue_service_issues',
                'table' => 'tl_issue',
                'act' => 'edit',
                'id' => (int) $issue['id'],
                'rt' => $container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
            ]);
            $html .= sprintf(
                '<tr class="%s"><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                0 === $index % 2 ? 'even' : 'odd',
                StringUtil::specialchars($href),
                StringUtil::specialchars((string) ($issue['ticket_number'] ?? '')),
                StringUtil::specialchars((string) $issue['title']),
                StringUtil::specialchars((string) $issue['service_title']),
                StringUtil::specialchars((string) $issue['status_title']),
                StringUtil::specialchars((string) $issue['priority']),
                StringUtil::specialchars((string) $issue['last_public_activity_at']),
            );
        }

        return $html.'</tbody></table>';
    }

    protected function compile(): void
    {
    }
}