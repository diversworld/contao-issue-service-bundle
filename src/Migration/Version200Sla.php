<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\{AbstractMigration, MigrationResult};
use Doctrine\DBAL\Connection;

/** Additive, restartable DBAL migration; DCA remains the schema source of truth. */
final class Version200Sla extends AbstractMigration
{
    private const TABLES = ['tl_issue_sla_calendar', 'tl_issue_sla_contract', 'tl_issue_sla_priority', 'tl_issue_sla_webhook', 'tl_issue_sla_level', 'tl_issue_sla', 'tl_issue_sla_escalation', 'tl_issue_sla_history', 'tl_issue_license', 'tl_issue_license_validation', 'tl_issue', 'tl_issue_service'];
    public function __construct(private readonly Connection $db) {}
    public function shouldRun(): bool
    {
        if (!$this->db->createSchemaManager()->tablesExist(['tl_issue', 'tl_issue_service'])) return false;
        return $this->statements() !== [] || !$this->db->fetchOne('SELECT id FROM tl_issue_license WHERE id=1');
    }
    public function run(): MigrationResult
    {
        foreach ($this->statements() as $sql) $this->db->executeStatement($sql);
        foreach (['bronze' => 'Bronze', 'silver' => 'Silber', 'gold' => 'Gold', 'platinum' => 'Platin'] as $key => $title) {
            if (!$this->db->fetchOne('SELECT id FROM tl_issue_sla_level WHERE level_key=?', [$key])) $this->db->insert('tl_issue_sla_level', ['level_key' => $key, 'title' => $title, 'tstamp' => time()]);
        }
        if (!$this->db->fetchOne('SELECT id FROM tl_issue_license WHERE id=1')) $this->db->insert('tl_issue_license', ['id' => 1, 'token' => '', 'tstamp' => time()]);
        return $this->createResult(true, 'SLA- und Lizenzschema ergänzt.');
    }
    /** @return list<string> */
    private function statements(): array
    {
        $sql = [];
        $manager = $this->db->createSchemaManager();
        foreach (self::TABLES as $table) {
            require dirname(__DIR__, 2).'/contao/dca/'.$table.'.php';
            $dca = $GLOBALS['TL_DCA'][$table];
            $exists = $manager->tablesExist([$table]);
            $columns = $exists ? $manager->listTableColumns($table) : [];
            $definitions = [];
            foreach ($dca['fields'] as $name => $field) {
                if (!isset($field['sql']) || isset($columns[$name])) continue;
                if (in_array($table, ['tl_issue', 'tl_issue_service'], true) && !str_starts_with($name, 'sla_') && !in_array($name, ['response_due_at','resolve_due_at','first_response_at'], true)) continue;
                $definition = '`'.$name.'` '.$field['sql'];
                if ($exists) $sql[] = 'ALTER TABLE '.$table.' ADD '.$definition;
                else $definitions[] = $definition;
            }
            $indexes = $exists ? $manager->listTableIndexes($table) : [];
            foreach ($dca['config']['sql']['keys'] as $fields => $type) {
                if (in_array($table, ['tl_issue', 'tl_issue_service'], true) && !str_starts_with($fields, 'sla_')) continue;
                $parts = explode(',', $fields);
                $found = false;
                foreach ($indexes as $index) if ($index->getColumns() === $parts) $found = true;
                if ($found) continue;
                $definition = ($type === 'primary' ? 'PRIMARY KEY' : ($type === 'unique' ? 'UNIQUE INDEX ' : 'INDEX ').str_replace(',', '_', $fields)).' ('.implode(',', $parts).')';
                if ($exists) $sql[] = 'ALTER TABLE '.$table.' ADD '.$definition;
                else $definitions[] = $definition;
            }
            if (!$exists) $sql[] = 'CREATE TABLE '.$table.' ('.implode(',', $definitions).') DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        return $sql;
    }
}
