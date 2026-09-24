<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

final class Version102TicketCategories extends AbstractMigration
{
    private const CATEGORIES = [
        'hardware' => ['Hardware', 'PCs, Laptops, Drucker, Monitore, Mobilgeraete'],
        'software' => ['Software', 'Anwendungen, Betriebssysteme, Installationen'],
        'users_permissions' => ['Benutzer & Berechtigungen', 'Accounts, Passwoerter, Rollen, Zugriffe'],
        'network_communication' => ['Netzwerk & Kommunikation', 'WLAN, LAN, VPN, Internet, Telefonie'],
        'email_collaboration' => ['E-Mail & Collaboration', 'Outlook, Teams, Exchange, SharePoint'],
        'infrastructure_server' => ['Infrastruktur & Server', 'Server, Datenbanken, Storage'],
        'interfaces_integration' => ['Schnittstellen & Integration', 'Datenimporte, APIs, Systemanbindungen'],
        'processes_workflow' => ['Prozesse & Workflow', 'Geschaeftsprozesse, Automatisierungen'],
        'reporting_data' => ['Reporting & Daten', 'Berichte, Auswertungen, BI'],
        'security_compliance' => ['Sicherheit & Compliance', 'Sicherheitsvorfaelle, Datenschutz'],
        'training_documentation' => ['Schulung & Dokumentation', 'Anleitungen, Trainings, Wissensmanagement'],
        'other' => ['Sonstiges', 'Nicht zuordenbare Themen'],
    ];

    private const OLD_ALIASES = ['general', 'incident', 'bug', 'improvement', 'question'];

    public function __construct(private readonly Connection $db)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->db->createSchemaManager()->tablesExist(['tl_issue_service', 'tl_issue_category'])) {
            return false;
        }

        $columns = array_change_key_case($this->db->createSchemaManager()->listTableColumns('tl_issue_category'), CASE_LOWER);

        if (!isset($columns['description'])) {
            return true;
        }

        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM tl_issue_service s WHERE NOT EXISTS (SELECT 1 FROM tl_issue_category c WHERE c.service_id = s.id AND c.alias = :alias) LIMIT 1',
            ['alias' => 'hardware'],
        );
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->db->createSchemaManager();
        $columns = array_change_key_case($schemaManager->listTableColumns('tl_issue_category'), CASE_LOWER);

        if (!isset($columns['description'])) {
            $this->db->executeStatement('ALTER TABLE tl_issue_category ADD description VARCHAR(255) DEFAULT NULL AFTER alias');
        }

        $oldAliasList = "'".implode("','", self::OLD_ALIASES)."'";

        $this->db->executeStatement(
            'UPDATE tl_issue SET category_id = NULL WHERE category_id IN (SELECT id FROM tl_issue_category WHERE alias IN ('.$oldAliasList.'))',
        );

        $this->db->executeStatement('DELETE FROM tl_issue_category WHERE alias IN ('.$oldAliasList.')');

        $created = 0;
        $updated = 0;

        foreach (self::CATEGORIES as $alias => [$title, $description]) {
            $updated += $this->db->executeStatement(
                'UPDATE tl_issue_category SET title = :title, description = :description, published = 1 WHERE alias = :alias',
                ['title' => $title, 'description' => $description, 'alias' => $alias],
            );

            $created += $this->db->executeStatement(
                'INSERT INTO tl_issue_category (tstamp, service_id, title, alias, description, published)
                 SELECT :tstamp, s.id, :title, :alias, :description, 1
                 FROM tl_issue_service s
                 WHERE NOT EXISTS (
                     SELECT 1 FROM tl_issue_category c WHERE c.service_id = s.id AND c.alias = :alias_check
                 )',
                [
                    'tstamp' => time(),
                    'title' => $title,
                    'alias' => $alias,
                    'description' => $description,
                    'alias_check' => $alias,
                ],
            );
        }

        return $this->createResult(true, sprintf('Created %d and updated %d ticket categories.', $created, $updated));
    }
}