<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

use Doctrine\DBAL\Connection;
use Diversworld\ContaoIssueServiceBundle\Application\License\PremiumFeatureResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** At-least-once delivery. Receivers deduplicate the stable Idempotency-Key. */
final class SlaWebhookDispatcher
{
    public function __construct(private readonly Connection $db, private readonly PremiumFeatureResolver $premium, private readonly HttpClientInterface $client) {}

    public function dispatch(int $limit = 50): int
    {
        if ($this->db->isTransactionActive()) return 0;
        $now = time();
        $rows = $this->db->fetchAllAssociative("SELECT w.*,e.webhook_url,e.published,i.deleted_at,i.sla_id,e.sla_id rule_sla_id FROM tl_issue_sla_webhook w JOIN tl_issue_sla_escalation e ON e.id=w.rule_id JOIN tl_issue i ON i.id=w.issue_id WHERE w.status IN ('pending','retry','sending') AND w.next_attempt_at<=? ORDER BY w.id LIMIT ".max(1, min(200, $limit)), [$now]);
        $sent = 0;
        foreach ($rows as $row) {
            if (!$this->premium->enabled()) break;
            // Atomic lease recovers crashed workers without sending concurrently.
            if (!$this->db->executeStatement("UPDATE tl_issue_sla_webhook SET status='sending', next_attempt_at=? WHERE id=? AND status IN ('pending','retry','sending') AND next_attempt_at<=?", [$now + 120, $row['id'], $now])) continue;
            try {
                if (!$row['published'] || $row['deleted_at'] || $row['sla_id'] !== $row['rule_sla_id'] || !$row['webhook_url']) {
                    $this->db->update('tl_issue_sla_webhook', ['status' => 'skipped'], ['id' => $row['id']]);
                    continue;
                }
                $url = $row['webhook_url'];
                if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_USER) !== null) throw new \DomainException('HTTPS URL required.');
                $response = $this->client->request('POST', $url, ['body' => $row['payload'], 'headers' => ['Content-Type' => 'application/json', 'Idempotency-Key' => 'sla-'.$row['issue_id'].'-'.$row['event_key']], 'timeout' => 10, 'max_duration' => 15, 'max_redirects' => 0]);
                $code = $response->getStatusCode();
                $response->cancel();
                if ($code < 200 || $code >= 300) throw new \RuntimeException('HTTP '.$code);
                $this->db->update('tl_issue_sla_webhook', ['status' => 'sent', 'sent_at' => time(), 'attempts' => (int) $row['attempts'] + 1, 'last_error' => null], ['id' => $row['id']]);
                ++$sent;
            } catch (\Throwable $error) {
                $attempts = (int) $row['attempts'] + 1;
                $this->db->update('tl_issue_sla_webhook', ['status' => 'retry', 'attempts' => $attempts, 'next_attempt_at' => time() + min(86400, 60 * 2 ** min($attempts, 10)), 'last_error' => substr($error->getMessage(), 0, 1000)], ['id' => $row['id']]);
            }
        }
        return $sent;
    }
}
