<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\{AbstractMigration, MigrationResult};
use Doctrine\DBAL\Connection;

final class Version122ExampleWorkflow extends AbstractMigration
{
    private const RULES = [
        ['new', 'triage', 'agent', 0],
        ['new', 'in_progress', 'agent', 0],
        ['triage', 'in_progress', 'agent', 0],
        ['triage', 'rejected', 'agent', 1],
        ['in_progress', 'waiting_user', 'agent', 1],
        ['waiting_user', 'in_progress', 'agent', 0],
        ['in_progress', 'resolved', 'agent', 1],
        ['resolved', 'closed', 'agent', 0],
        ['resolved', 'in_progress', 'agent', 1],
        ['new', 'triage', 'manager', 0],
        ['new', 'in_progress', 'manager', 0],
        ['triage', 'in_progress', 'manager', 0],
        ['triage', 'rejected', 'manager', 1],
        ['in_progress', 'waiting_user', 'manager', 1],
        ['waiting_user', 'in_progress', 'manager', 0],
        ['in_progress', 'resolved', 'manager', 1],
        ['resolved', 'closed', 'manager', 0],
        ['resolved', 'in_progress', 'manager', 1],
        ['closed', 'in_progress', 'manager', 1],
        ['rejected', 'triage', 'manager', 1],
        ['waiting_user', 'in_progress', 'member', 1],
        ['resolved', 'closed', 'member', 0],
        ['resolved', 'in_progress', 'member', 1],
    ];

    public function __construct(private readonly Connection $db) {}

    private function missingRules(): array
    {
        if (!$this->db->createSchemaManager()->tablesExist(['tl_issue_status', 'tl_issue_transition'])) return [];
        $statuses = $this->db->fetchAllKeyValue('SELECT status_key, id FROM tl_issue_status');
        $missing = [];
        foreach (self::RULES as [$from, $to, $role, $comment]) {
            if (!isset($statuses[$from], $statuses[$to])) continue;
            $source = (int) $statuses[$from];
            $target = (int) $statuses[$to];
            if ($this->db->fetchOne('SELECT id FROM tl_issue_transition WHERE from_status_id=? AND to_status_id=? AND role_key=?', [$source, $target, $role])) continue;
            $missing[] = ['tstamp' => time(), 'from_status_id' => $source, 'to_status_id' => $target, 'role_key' => $role, 'require_public_comment' => $comment, 'published' => 1];
        }
        return $missing;
    }

    public function shouldRun(): bool
    {
        return $this->missingRules() !== [];
    }

    public function run(): MigrationResult
    {
        $created = $this->db->transactional(function (): int {
            $rules = $this->missingRules();
            foreach ($rules as $rule) $this->db->insert('tl_issue_transition', $rule);
            return count($rules);
        });
        return $this->createResult(true, sprintf('%d Beispiel-Workflowregeln angelegt. Vorhandene Regeln bleiben unverändert.', $created));
    }
}
