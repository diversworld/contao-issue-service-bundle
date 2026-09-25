<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\{AbstractMigration, MigrationResult};
use Doctrine\DBAL\Connection;

final class Version120WorkflowGroups extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}
    public function shouldRun(): bool
    {
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_user_group');
        return !isset($columns['issue_workflow_role'], $columns['issue_workflow_services']);
    }
    public function run(): MigrationResult
    {
        $columns = $this->db->createSchemaManager()->listTableColumns('tl_user_group');
        foreach (['issue_workflow_role' => "varchar(16) NOT NULL default ''", 'issue_workflow_services' => 'blob NULL'] as $name => $sql) {
            if (!isset($columns[$name])) $this->db->executeStatement('ALTER TABLE tl_user_group ADD '.$name.' '.$sql);
        }
        return $this->createResult(true, 'Workflow-Rollen und Servicezuordnung für Benutzergruppen ergänzt.');
    }
}
