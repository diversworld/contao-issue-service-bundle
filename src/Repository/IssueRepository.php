<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Repository;

use Doctrine\DBAL\Connection;
use Diversworld\ContaoIssueServiceBundle\Domain\Dto\IssueListFilter;

final class IssueRepository
{
    private const UUID_SQL = "LOWER(CONCAT(SUBSTR(HEX(i.uuid),1,8),'-',SUBSTR(HEX(i.uuid),9,4),'-',SUBSTR(HEX(i.uuid),13,4),'-',SUBSTR(HEX(i.uuid),17,4),'-',SUBSTR(HEX(i.uuid),21)))";

    public function __construct(private readonly Connection $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByUuid(string $uuid): ?array
    {
        $result = $this->db->fetchAssociative(
            'SELECT i.*,'.self::UUID_SQL.' uuid,s.title service_title,st.status_key,st.title status_title FROM tl_issue i JOIN tl_issue_service s ON s.id=i.service_id JOIN tl_issue_status st ON st.id=i.status_id WHERE HEX(i.uuid)=REPLACE(UPPER(:uuid),\'-\',\'\') AND i.deleted_at IS NULL',
            ['uuid' => $uuid],
        );

        return false === $result ? null : $result;
    }

    /** @return array<string, mixed>|null */
    public function findAuthorized(string $uuid, ?int $memberId, ?string $guestHash): ?array
    {
        $sql = 'SELECT i.*,'.self::UUID_SQL.' uuid,s.title service_title,st.status_key,st.title status_title FROM tl_issue i JOIN tl_issue_service s ON s.id=i.service_id JOIN tl_issue_status st ON st.id=i.status_id WHERE HEX(i.uuid)=REPLACE(UPPER(:uuid),\'-\',\'\') AND i.deleted_at IS NULL AND ((i.member_id IS NOT NULL AND i.member_id=:member) OR (i.guest_access_hash IS NOT NULL AND i.guest_access_hash=:hash))';
        $result = $this->db->fetchAssociative($sql, ['uuid' => $uuid, 'member' => $memberId ?? 0, 'hash' => $guestHash ?? '']);

        return false === $result ? null : $result;
    }

    /** @return list<array<string, mixed>> */
    public function list(IssueListFilter $filter): array
    {
        $where = ['i.deleted_at IS NULL'];
        $parameters = [];

        if (null !== $filter->memberId) {
            $where[] = 'i.member_id=:member';
            $parameters['member'] = $filter->memberId;
        }
        if (null !== $filter->serviceId) {
            $where[] = 'i.service_id=:service';
            $parameters['service'] = $filter->serviceId;
        }
        if (null !== $filter->statusId) {
            $where[] = 'i.status_id=:status';
            $parameters['status'] = $filter->statusId;
        }
        if (null !== $filter->assignedUserId) {
            $where[] = 'i.assigned_user_id=:assignee';
            $parameters['assignee'] = $filter->assignedUserId;
        }

        $offset = max(0, ($filter->page - 1) * $filter->limit);

        return $this->db->fetchAllAssociative(
            'SELECT i.*,'.self::UUID_SQL.' uuid,s.title service_title,st.title status_title,st.status_key FROM tl_issue i JOIN tl_issue_service s ON s.id=i.service_id JOIN tl_issue_status st ON st.id=i.status_id WHERE '.implode(' AND ', $where).' ORDER BY i.last_public_activity_at DESC LIMIT '.(int) $filter->limit.' OFFSET '.$offset,
            $parameters,
        );
    }

    /** @return list<array<string, mixed>> */
    public function publicTimeline(int $issueId): array
    {
        return $this->timeline($issueId, false);
    }

    /** @return list<array<string, mixed>> */
    public function timeline(int $issueId, bool $internal = false): array
    {
        $comments = $this->db->fetchAllAssociative(
            "SELECT id, 'comment' entry_type, body text, created_at, author_type, visibility FROM tl_issue_comment WHERE issue_id=:id".($internal ? '' : " AND visibility='public'"),
            ['id' => $issueId],
        );
        $events = $this->db->fetchAllAssociative(
            "SELECT id, 'history' entry_type, event_type, old_value, new_value, created_at, actor_type author_type FROM tl_issue_history WHERE issue_id=:id AND event_type IN ('issue_created', 'status_changed', 'priority_changed', 'assignment_changed', 'resolution_changed')".($internal ? '' : " AND event_type IN ('issue_created', 'status_changed', 'priority_changed')"),
            ['id' => $issueId],
        );
        $statuses = $this->db->fetchAllKeyValue('SELECT id, title FROM tl_issue_status');
        $priorities = ['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch', 'critical' => 'Kritisch'];
        foreach ($events as &$event) {
            $old = json_decode($event['old_value'] ?? '{}', true) ?: [];
            $new = json_decode($event['new_value'] ?? '{}', true) ?: [];
            $event['visibility'] = in_array($event['event_type'], ['assignment_changed', 'resolution_changed'], true) ? 'internal' : 'public';
            $event['text'] = match ($event['event_type']) {
                'issue_created' => 'Ticket erstellt.',
                'status_changed' => 'Status: '.($statuses[$old['status_id'] ?? 0] ?? '–').' → '.($statuses[$new['status_id'] ?? 0] ?? '–'),
                'priority_changed' => 'Priorität: '.($priorities[$old['priority'] ?? ''] ?? '–').' → '.($priorities[$new['priority'] ?? ''] ?? '–'),
                'assignment_changed' => 'Zuständigkeit geändert.',
                'resolution_changed' => 'Lösung bearbeitet.',
            };
        }
        unset($event);
        $entries = array_merge($comments, $events);
        usort($entries, static fn (array $a, array $b): int => [$a['created_at'], $a['entry_type'], (int) $a['id']] <=> [$b['created_at'], $b['entry_type'], (int) $b['id']]);

        return $entries;
    }

    public function connection(): Connection
    {
        return $this->db;
    }
}
