<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Diversworld\ContaoIssueServiceBundle\Application\SettingsList;
use Doctrine\DBAL\Connection;

final class Version110Profiles extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function shouldRun(): bool
    {
        $schema = $this->db->createSchemaManager();
        return $schema->tablesExist(['tl_issue_settings']) && (
            !$schema->tablesExist(['tl_issue_profile'])
            || !isset($schema->listTableColumns('tl_module')['issue_profile_id'])
            || !isset($schema->listTableColumns('tl_issue')['profile_id'])
            || !(int) $this->db->fetchOne('SELECT COUNT(*) FROM tl_issue_profile')
        );
    }

    public function run(): MigrationResult
    {
        require dirname(__DIR__, 2).'/contao/dca/tl_issue_profile.php';
        $fields = $GLOBALS['TL_DCA']['tl_issue_profile']['fields'];
        if (!$this->db->createSchemaManager()->tablesExist(['tl_issue_profile'])) {
            $columns = [];
            foreach ($fields as $name => $definition) {
                $columns[] = '`'.$name.'` '.$definition['sql'];
            }
            $this->db->executeStatement('CREATE TABLE tl_issue_profile ('.implode(', ', $columns).', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
        }
        foreach (['tl_module' => 'issue_profile_id', 'tl_issue' => 'profile_id'] as $table => $column) {
            if (!isset($this->db->createSchemaManager()->listTableColumns($table)[$column])) {
                $this->db->executeStatement('ALTER TABLE '.$table.' ADD '.$column.' INT UNSIGNED NOT NULL DEFAULT 0');
            }
        }
        if (!(int) $this->db->fetchOne('SELECT COUNT(*) FROM tl_issue_profile')) {
            $old = $this->db->fetchAllKeyValue('SELECT setting_key, setting_value FROM tl_issue_settings');
            $row = ['title' => 'Standardkonfiguration', 'description' => 'Aus den bisherigen globalen Einstellungen übernommen.', 'tstamp' => time()];
            foreach (['ticket_pattern', 'require_login', 'max_file_size', 'max_files_per_issue', 'service_scoped_permissions'] as $key) {
                $row[$key] = $old[$key] ?? $fields[$key]['default'];
            }
            foreach (['allowed_extensions', 'reopen_roles'] as $key) {
                $row[$key] = serialize(isset($old[$key]) ? SettingsList::decode($old[$key]) : $fields[$key]['default']);
            }
            $row['mail_recipients'] = implode("\n", SettingsList::decode($old['mail_recipients'] ?? '[]'));
            $row['retention_days'] = (int) ((json_decode($old['retention_policy'] ?? '{}', true) ?: [])['closed_days'] ?? 730);
            $this->db->insert('tl_issue_profile', $row);
        }
        return $this->createResult(true, 'Konfigurationsprofile angelegt; bisherige Einstellungen als Standardprofil übernommen.');
    }
}
