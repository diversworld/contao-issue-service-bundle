<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

use Doctrine\DBAL\Connection;

final class SlaHistory
{
    public function __construct(private readonly Connection $db, private readonly string $auditKey) {}

    /** Call under the ticket row lock and in the same transaction as the change. */
    /** @param array<string, mixed> $details */
    public function append(int $issueId, string $event, array $details, int $now, int $actorId = 0): void
    {
        $previous = (string) $this->db->fetchOne('SELECT entry_hash FROM tl_issue_sla_history WHERE issue_id=? ORDER BY id DESC LIMIT 1', [$issueId]);
        $json = json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $hash = hash_hmac('sha256', implode('|', [$previous, $issueId, $event, $now, $actorId, $json]), $this->auditKey);
        $this->db->insert('tl_issue_sla_history', ['issue_id' => $issueId, 'event_type' => $event, 'details' => $json,
            'created_at' => $now, 'actor_id' => $actorId, 'previous_hash' => $previous, 'entry_hash' => $hash]);
    }

    public function verify(int $issueId): bool
    {
        $previous = '';
        foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_issue_sla_history WHERE issue_id=? ORDER BY id', [$issueId]) as $row) {
            $expected = hash_hmac('sha256', implode('|', [$previous, $issueId, $row['event_type'], $row['created_at'], $row['actor_id'], $row['details']]), $this->auditKey);
            if ($row['previous_hash'] !== $previous || !hash_equals($expected, $row['entry_hash'])) return false;
            $previous = $row['entry_hash'];
        }
        return true;
    }
}
