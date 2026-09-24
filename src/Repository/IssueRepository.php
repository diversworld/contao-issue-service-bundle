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
        return $this->db->fetchAllAssociative(
            "SELECT id,'comment' entry_type,body text,created_at,author_type FROM tl_issue_comment WHERE issue_id=:id AND visibility='public' UNION ALL SELECT id,'history' entry_type,event_type text,created_at,actor_type FROM tl_issue_history WHERE issue_id=:id2 ORDER BY created_at",
            ['id' => $issueId, 'id2' => $issueId],
        );
    }

    public function connection(): Connection
    {
        return $this->db;
    }
}
