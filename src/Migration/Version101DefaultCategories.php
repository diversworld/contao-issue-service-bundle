<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

final class Version101DefaultCategories extends AbstractMigration
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

    public function __construct(private readonly Connection $db)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->db->createSchemaManager()->tablesExist(['tl_issue_service', 'tl_issue_category'])) {
            return false;
        }

        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM tl_issue_service s WHERE NOT EXISTS (SELECT 1 FROM tl_issue_category c WHERE c.service_id = s.id) LIMIT 1',
        );
    }

    public function run(): MigrationResult
    {
        $created = 0;

        foreach (self::CATEGORIES as $alias => [$title, $description]) {
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

        return $this->createResult(true, sprintf('Created %d default issue categories.', $created));
    }
}