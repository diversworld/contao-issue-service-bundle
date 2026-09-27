<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

use Doctrine\DBAL\{Connection, ArrayParameterType};
use Diversworld\ContaoIssueServiceBundle\Application\License\PremiumFeatureResolver;

final class SlaReportingService
{
    public function __construct(private readonly Connection $db, private readonly PremiumFeatureResolver $premium) {}

    /** Null scope is reserved for administrators and CLI. Empty scope returns no tickets. */
    /** @param list<int>|null $serviceIds
     * @return array<string, mixed> */
    public function report(?array $serviceIds = null, ?string $from = null, ?string $until = null): array
    {
        $this->premium->requireSla();
        $where = 'i.deleted_at IS NULL AND i.sla_id>0';
        $params = []; $types = [];
        if ($serviceIds !== null) { $where .= ' AND i.service_id IN (?)'; $params[] = $serviceIds ?: [0]; $types[] = ArrayParameterType::INTEGER; }
        if ($from !== null) { $where .= ' AND i.created_at>=?'; $params[] = $from; $types[] = \Doctrine\DBAL\ParameterType::STRING; }
        if ($until !== null) { $where .= ' AND i.created_at<?'; $params[] = $until; $types[] = \Doctrine\DBAL\ParameterType::STRING; }
        $metrics = $this->db->fetchAssociative("SELECT COUNT(*) total, COALESCE(SUM(CASE WHEN i.sla_breached=1 THEN 1 ELSE 0 END),0) breached, COALESCE(SUM(CASE WHEN i.sla_state='paused' THEN 1 ELSE 0 END),0) completed, COALESCE(SUM(CASE WHEN i.sla_state='paused' AND i.sla_breached=0 THEN 1 ELSE 0 END),0) completed_on_time FROM tl_issue i WHERE ".$where, $params, $types);
        if (!$metrics) throw new \RuntimeException('SLA-Bericht konnte nicht ermittelt werden.');
        $completed = (int) $metrics['completed'];
        $metrics['compliance_percent'] = $completed ? round(100 * (int) $metrics['completed_on_time'] / $completed, 2) : null;
        $violations = $this->db->fetchAllAssociative('SELECT i.id,i.ticket_number,i.title,i.service_id,i.response_due_at,i.resolve_due_at,i.sla_breached_at,i.sla_state FROM tl_issue i WHERE '.$where.' AND i.sla_breached=1 ORDER BY i.sla_breached_at DESC,i.id DESC LIMIT 200', $params, $types);
        return ['metrics' => $metrics, 'violations' => $violations, 'monitor_limit' => 200, 'generated_at' => time()];
    }
}
