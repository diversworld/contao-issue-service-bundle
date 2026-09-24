<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\FrontendModule;

use Contao\FrontendUser;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;

final class IssueListModule extends Module
{
    private const UUID_SQL = "LOWER(CONCAT(SUBSTR(HEX(i.uuid),1,8),'-',SUBSTR(HEX(i.uuid),9,4),'-',SUBSTR(HEX(i.uuid),13,4),'-',SUBSTR(HEX(i.uuid),17,4),'-',SUBSTR(HEX(i.uuid),21)))";

    protected function compile(): void
    {
    }

    public function generate(): string
    {
        $user = FrontendUser::getInstance();

        if (!$user->id) {
            return '<div class="mod_issue_service_list"><p class="empty">'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['issue_service_login_required'] ?? 'Bitte melden Sie sich an, um Ihre Issues zu sehen.').'</p></div>';
        }

        $container = System::getContainer();
        $connection = $container->get('database_connection');
        \assert($connection instanceof Connection);
        $router = $container->get('router');

        $issues = $connection->fetchAllAssociative(
            'SELECT i.ticket_number, i.title, i.last_public_activity_at, '.self::UUID_SQL.' uuid, s.title service_title, st.title status_title
             FROM tl_issue i
             JOIN tl_issue_service s ON s.id = i.service_id
             JOIN tl_issue_status st ON st.id = i.status_id
             WHERE i.deleted_at IS NULL AND i.member_id = :member
             ORDER BY i.last_public_activity_at DESC, i.id DESC
             LIMIT 20',
            ['member' => (int) $user->id],
        );

        if ([] === $issues) {
            return '<div class="mod_issue_service_list"><p class="empty">'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['issue_service_no_issues'] ?? 'Es sind keine Issues vorhanden.').'</p></div>';
        }

        $html = '<div class="mod_issue_service_list"><table><thead><tr><th>Ticket</th><th>Titel</th><th>Service</th><th>Status</th><th>Aktualisiert</th></tr></thead><tbody>';

        foreach ($issues as $issue) {
            $html .= sprintf(
                '<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                StringUtil::specialchars($router->generate('issue_service_detail', ['uuid' => (string) $issue['uuid']])),
                StringUtil::specialchars((string) $issue['ticket_number']),
                StringUtil::specialchars((string) $issue['title']),
                StringUtil::specialchars((string) $issue['service_title']),
                StringUtil::specialchars((string) $issue['status_title']),
                StringUtil::specialchars((string) $issue['last_public_activity_at']),
            );
        }

        return $html.'</tbody></table></div>';
    }
}