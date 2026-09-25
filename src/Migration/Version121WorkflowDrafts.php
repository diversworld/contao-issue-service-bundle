<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\{AbstractMigration, MigrationResult};
use Doctrine\DBAL\Connection;

final class Version121WorkflowDrafts extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    public function shouldRun(): bool
    {
        $schema = $this->db->createSchemaManager();
        if (!$schema->tablesExist(['tl_issue_transition'])) return false;
        return $schema->listTableColumns('tl_issue_transition')['to_status_id']->getNotnull();
    }

    public function run(): MigrationResult
    {
        $this->db->executeStatement('ALTER TABLE tl_issue_transition MODIFY to_status_id INT UNSIGNED NULL DEFAULT NULL');
        return $this->createResult(true, 'Workflow-Kopien ohne Zielstatus als Entwurf ermöglicht.');
    }
}
