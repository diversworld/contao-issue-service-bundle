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
        foreach ([$from, $until] as $date) {
            if ($date !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || (new \DateTimeImmutable($date))->format('Y-m-d') !== $date)) throw new \InvalidArgumentException('Ungültiges Berichtsdatum.');
        }
        if ($from !== null && $until !== null && $from >= $until) throw new \InvalidArgumentException('Ungültiger Berichtszeitraum.');
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
        $calendar = new BusinessCalendar();
        $totals = ['response' => 0, 'resolve' => 0]; $samples = ['response' => 0, 'resolve' => 0];
        $categories = []; $agents = []; $critical = []; $escalations = [];
        $last = 0;
        do {
            $batch = $this->db->fetchAllAssociative('SELECT i.* FROM tl_issue i WHERE '.$where.' AND i.id>? ORDER BY i.id LIMIT 500', [...$params, $last], [...$types, \Doctrine\DBAL\ParameterType::INTEGER]);
            foreach ($batch as $issue) {
                $last = (int) $issue['id'];
                $category = (int) $issue['category_id']; $agent = (int) $issue['assigned_user_id'];
                $categories[$category] = ($categories[$category] ?? 0) + 1;
                $agents[$agent] ??= ['agent_id' => $agent, 'tickets' => 0, 'breached' => 0, 'completed' => 0, 'response_seconds' => 0, 'response_samples' => 0, 'resolve_seconds' => 0, 'resolve_samples' => 0];
                ++$agents[$agent]['tickets'];
                $agents[$agent]['breached'] += (int) (bool) $issue['sla_breached'];
                $agents[$agent]['completed'] += (int) ($issue['sla_state'] === 'paused');
                $snapshot = json_decode($issue['sla_snapshot'] ?: 'null', true, 32, JSON_THROW_ON_ERROR);
                if ($snapshot) {
                    foreach (['response' => $issue['first_response_at'], 'resolve' => $issue['sla_state'] === 'paused' ? $issue['sla_paused_at'] : null] as $kind => $at) {
                        if (!$at || !$issue[$kind.'_due_at']) continue;
                        // Due dates already include paused periods; measure consumed business-time budget.
                        $duration = max(0, (int) $snapshot[$kind] - $calendar->between((int) $at, (int) $issue[$kind.'_due_at'], $snapshot['timezone'], $snapshot['hours'], $snapshot['holidays'], $snapshot['maintenance'] ?? []));
                        $totals[$kind] += $duration; ++$samples[$kind];
                        $agents[$agent][$kind.'_seconds'] += $duration; ++$agents[$agent][$kind.'_samples'];
                    }
                }
                if ($issue['sla_state'] === 'active' && ($issue['priority'] === 'critical' || (!$issue['first_response_at'] && (int) $issue['response_due_at'] <= time() + 3600) || (int) $issue['resolve_due_at'] <= time() + 3600)) {
                    if (count($critical) < 200) $critical[] = array_intersect_key($issue, array_flip(['id','ticket_number','title','priority','response_due_at','resolve_due_at']));
                    $metrics['critical'] = (int) ($metrics['critical'] ?? 0) + 1;
                }
            }
        } while (count($batch) === 500);
        $metrics['critical'] ??= 0;
        foreach ($totals as $kind => $total) {
            $metrics['average_'.$kind.'_seconds'] = $samples[$kind] ? round($total / $samples[$kind], 2) : null;
            $metrics[$kind.'_samples'] = $samples[$kind];
        }
        foreach ($agents as &$agent) {
            $agent['name'] = $agent['agent_id'] ? (string) ($this->db->fetchOne('SELECT name FROM tl_user WHERE id=?', [$agent['agent_id']]) ?: '#'.$agent['agent_id']) : 'Nicht zugewiesen';
            foreach (['response', 'resolve'] as $kind) $agent['average_'.$kind.'_seconds'] = $agent[$kind.'_samples'] ? round($agent[$kind.'_seconds'] / $agent[$kind.'_samples'], 2) : null;
        }
        unset($agent);
        arsort($categories);
        $categoryRows = [];
        foreach ($categories as $id => $count) $categoryRows[] = ['category_id' => $id, 'name' => $id ? (string) ($this->db->fetchOne('SELECT title FROM tl_issue_category WHERE id=?', [$id]) ?: '#'.$id) : 'Ohne Kategorie', 'tickets' => $count];
        $cycleExpression = $this->db->getDatabasePlatform()->getConcatExpression("'escalated_resolve_'", 'i.sla_cycle', "'_'");
        $responseExpression = $this->db->getDatabasePlatform()->getConcatExpression("'escalated_response_'", 'i.sla_cycle', "'_'");
        $openWhere = $where." AND i.sla_state='active' AND h.event_type LIKE 'escalated_%' AND (SUBSTR(h.event_type,1,LENGTH(".$cycleExpression.")) = ".$cycleExpression." OR (i.first_response_at IS NULL AND SUBSTR(h.event_type,1,LENGTH(".$responseExpression.")) = ".$responseExpression."))";
        $metrics['open_escalations'] = (int) $this->db->fetchOne('SELECT COUNT(*) FROM tl_issue_sla_history h JOIN tl_issue i ON i.id=h.issue_id WHERE '.$openWhere, $params, $types);
        $escalations = $this->db->fetchAllAssociative('SELECT i.id,i.ticket_number,i.title,h.event_type,h.created_at FROM tl_issue_sla_history h JOIN tl_issue i ON i.id=h.issue_id WHERE '.$openWhere.' ORDER BY h.id DESC LIMIT 200', $params, $types);
        return ['metrics' => $metrics, 'violations' => $violations, 'critical_tickets' => $critical, 'open_escalations' => $escalations,
            'categories' => $categoryRows,
            'agents' => array_values($agents), 'from' => $from, 'until' => $until, 'monitor_limit' => 200, 'generated_at' => time()];
    }
}
