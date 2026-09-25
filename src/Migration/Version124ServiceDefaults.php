<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\{AbstractMigration, MigrationResult};
use Doctrine\DBAL\Connection;

final class Version124ServiceDefaults extends AbstractMigration
{
    public function __construct(private readonly Connection $db) {}

    private function missingDefaults(): array
    {
        $schema = $this->db->createSchemaManager();
        if (!$schema->tablesExist(['tl_issue_service'])) return [];
        $columns = $schema->listTableColumns('tl_issue_service');
        return array_values(array_filter(['title', 'alias'], static fn (string $field): bool => isset($columns[$field]) && $columns[$field]->getDefault() === null));
    }

    public function shouldRun(): bool
    {
        return $this->missingDefaults() !== [];
    }

    public function run(): MigrationResult
    {
        foreach ($this->missingDefaults() as $field) {
            $this->db->executeStatement("ALTER TABLE tl_issue_service ALTER COLUMN $field SET DEFAULT ''");
        }
        return $this->createResult(true, 'Standardwerte für neue Service-Datensätze ergänzt.');
    }
}
