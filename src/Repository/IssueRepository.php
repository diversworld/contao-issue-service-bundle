<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Repository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Vendor\ContaoIssueServiceBundle\Domain\Dto\IssueListFilter;
final class IssueRepository {
 public function __construct(private readonly Connection $db) {}
 public function findByUuid(string $uuid): ?array { $r=$this->db->fetchAssociative('SELECT i.*,s.title service_title,st.status_key,status.title status_title FROM tl_issue i JOIN tl_issue_service s ON s.id=i.service_id JOIN tl_issue_status st ON st.id=i.status_id JOIN tl_issue_status status ON status.id=i.status_id WHERE HEX(i.uuid)=REPLACE(UPPER(:uuid),\'-\',\'\') AND i.deleted_at IS NULL',['uuid'=>$uuid]); return false===$r?null:$r; }
 public function findAuthorized(string $uuid,?int $memberId,?string $guestHash): ?array { $sql='SELECT i.*,s.title service_title,st.status_key,st.title status_title FROM tl_issue i JOIN tl_issue_service s ON s.id=i.service_id JOIN tl_issue_status st ON st.id=i.status_id WHERE HEX(i.uuid)=REPLACE(UPPER(:uuid),\'-\',\'\') AND i.deleted_at IS NULL AND ((i.member_id IS NOT NULL AND i.member_id=:member) OR (i.guest_access_hash IS NOT NULL AND i.guest_access_hash=:hash))'; $r=$this->db->fetchAssociative($sql,['uuid'=>$uuid,'member'=>$memberId??0,'hash'=>$guestHash??'']); return false===$r?null:$r; }
 public function list(IssueListFilter $f): array { $w=['i.deleted_at IS NULL'];$p=[]; if(null!==$f->memberId){$w[]='i.member_id=:member';$p['member']=$f->memberId;} if(null!==$f->serviceId){$w[]='i.service_id=:service';$p['service']=$f->serviceId;} if(null!==$f->statusId){$w[]='i.status_id=:status';$p['status']=$f->statusId;} if(null!==$f->assignedUserId){$w[]='i.assigned_user_id=:assignee';$p['assignee']=$f->assignedUserId;} $off=max(0,($f->page-1)*$f->limit); return $this->db->fetchAllAssociative('SELECT i.*,s.title service_title,st.title status_title,st.status_key FROM tl_issue i JOIN tl_issue_service s ON s.id=i.service_id JOIN tl_issue_status st ON st.id=i.status_id WHERE '.implode(' AND ',$w).' ORDER BY i.last_public_activity_at DESC LIMIT '.(int)$f->limit.' OFFSET '.(int)$off,$p); }
 public function publicTimeline(int $issueId): array { return $this->db->fetchAllAssociative("SELECT id,'comment' entry_type,body text,created_at,author_type FROM tl_issue_comment WHERE issue_id=:id AND visibility='public' UNION ALL SELECT id,'history' entry_type,event_type text,created_at,actor_type FROM tl_issue_history WHERE issue_id=:id2 ORDER BY created_at",['id'=>$issueId,'id2'=>$issueId]); }
 public function connection():Connection{return $this->db;}
}
