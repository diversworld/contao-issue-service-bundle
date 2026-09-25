<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;
use Doctrine\DBAL\Connection;

final class RetentionService
{
    public function __construct(private readonly Connection $db, private readonly SettingsService $settings) {}

    private function days(): int
    {
        return max(1, (int) ($this->settings->json('retention_policy', ['closed_days' => 730])['closed_days'] ?? 730));
    }

    private function condition(): string
    {
        return 'i.closed_at IS NOT NULL AND i.closed_at < DATE_SUB(:now, INTERVAL COALESCE(p.retention_days, :days) DAY) AND i.deleted_at IS NULL';
    }

    public function preview(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'cutoff_scope' => 'default_profile; individual profile retention periods apply',
            'cutoff' => $now->modify('-'.$this->days().' days')->format('Y-m-d H:i:s'),
            'issues' => (int) $this->db->fetchOne('SELECT COUNT(*) FROM tl_issue i LEFT JOIN tl_issue_profile p ON p.id=i.profile_id WHERE '.$this->condition(), ['now' => $now->format('Y-m-d H:i:s'), 'days' => $this->days()]),
        ];
    }

    public function execute(bool $dryRun = true): array
    {
        $result = $this->preview();
        if (!$dryRun) {
            $this->db->executeStatement("UPDATE tl_issue i LEFT JOIN tl_issue_profile p ON p.id=i.profile_id SET i.member_id=NULL,i.guest_access_hash=NULL,i.title='[anonymized]',i.description='[anonymized]',i.resolution=NULL,i.deleted_at=NOW() WHERE ".$this->condition(), ['now' => date('Y-m-d H:i:s'), 'days' => $this->days()]);
        }
        return $result + ['dryRun' => $dryRun];
    }
}
