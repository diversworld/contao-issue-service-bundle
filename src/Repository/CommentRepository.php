<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Repository;
use Doctrine\DBAL\Connection;
final class CommentRepository { public function __construct(private readonly Connection $db){} public function add(int $issueId,string $visibility,string $authorType,?int $authorId,string $body):int{$this->db->insert('tl_issue_comment',['tstamp'=>time(),'issue_id'=>$issueId,'visibility'=>$visibility,'author_type'=>$authorType,'author_id'=>$authorId,'body'=>$body,'created_at'=>(new \DateTimeImmutable())->format('Y-m-d H:i:s')]);return (int)$this->db->lastInsertId();} }
