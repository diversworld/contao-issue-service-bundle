<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\{AbstractMigration, MigrationResult};
use Doctrine\DBAL\Connection;

final class Version123NotificationPreferences extends AbstractMigration
{
    public const EVENTS = ['issue_created', 'assignment_changed', 'status_changed', 'public_comment_added'];
    public function __construct(private readonly Connection $db) {}
    private function missing(): array
    {
        $schema = $this->db->createSchemaManager();
        $missing = [];
        foreach (['tl_issue_profile', 'tl_user', 'tl_member'] as $table) {
            if (!$schema->tablesExist([$table])) continue;
            $columns = $schema->listTableColumns($table);
            foreach (self::EVENTS as $event) {
                $field = 'issue_notify_'.$event;
                if (!isset($columns[$field])) $missing[] = [$table, $field, $table === 'tl_member' ? '0' : '1'];
            }
        }
        return $missing;
    }
    public function shouldRun(): bool { return $this->missing() !== []; }
    public function run(): MigrationResult
    {
        foreach ($this->missing() as [$table, $field, $default]) {
            $this->db->executeStatement("ALTER TABLE $table ADD $field CHAR(1) NOT NULL DEFAULT '$default'");
        }
        return $this->createResult(true, 'Benachrichtigungseinstellungen für Profile, Benutzer und Mitglieder ergänzt.');
    }
}
