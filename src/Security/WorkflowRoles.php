<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Security;

use Contao\BackendUser;
use Contao\FrontendUser;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Diversworld\ContaoIssueServiceBundle\Application\SettingsService;

final class WorkflowRoles
{
    public function __construct(private readonly Connection $db, private readonly SettingsService $settings) {}

    public function forUser(mixed $user, array $issue): array
    {
        if ($user instanceof FrontendUser) {
            return (int) $user->id > 0 && (int) $issue['member_id'] === (int) $user->id ? ['member'] : [];
        }
        if (!$user instanceof BackendUser || !(int) $user->id) {
            return [];
        }
        if ($user->isAdmin) {
            return ['agent', 'manager'];
        }
        if (!$user->hasAccess('issue_service_issues', 'modules')) {
            return [];
        }
        $scoped = $this->settings->forProfile((int) ($issue['profile_id'] ?? 0))->bool('service_scoped_permissions', true);
        return $this->forGroups($user->groups, (int) $issue['service_id'], $scoped);
    }

    public function forGroups(array $ids, int $serviceId, bool $scoped): array
    {
        $roles = [];
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            $group = $this->db->fetchAssociative('SELECT issue_workflow_role, issue_workflow_services, disable, start, stop FROM tl_user_group WHERE id=:id', ['id' => $id]);
            if (!$group || $group['disable'] || ((int) $group['start'] > time()) || ((int) $group['stop'] > 0 && (int) $group['stop'] <= time())) {
                continue;
            }
            $services = array_map('intval', StringUtil::deserialize($group['issue_workflow_services'], true));
            if ((!$scoped || in_array($serviceId, $services, true)) && in_array($group['issue_workflow_role'], ['agent', 'manager'], true)) {
                $roles[] = $group['issue_workflow_role'];
            }
            // Support previously configured service/group assignments as well.
            foreach ($this->db->fetchFirstColumn('SELECT role_key FROM tl_issue_service_group WHERE user_group_id=:group AND service_id=:service', ['group' => $id, 'service' => $serviceId]) as $role) {
                if (in_array($role, ['agent', 'manager'], true)) $roles[] = $role;
            }
        }
        return array_values(array_unique($roles));
    }
}
