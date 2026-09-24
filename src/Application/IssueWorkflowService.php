<?php
declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Application;

use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;

final class IssueWorkflowService 
{ 
    public function __construct(private readonly IssueRepository $issues,private readonly NotificationService $notifications)
    {} 
    
    public function transition(int $issueId,int $targetStatusId,string $role,string $actorType,?int $actorId,?string $comment=null):void
    {
        $db=$this->issues->connection();
        $db->transactional
        (
            function()use(
                $db,
                $issueId,
                $targetStatusId,
                $role,
                $actorType,
                $actorId,
                $comment
            ){
                $issue=$db->fetchAssociative('SELECT status_id,version FROM tl_issue WHERE id=:id FOR UPDATE',['id'=>$issueId]);
    
                if(false===$issue)
                    throw new \RuntimeException('Issue not found.');
                
                $rule=$db->fetchAssociative(
                    'SELECT require_public_comment 
                    FROM tl_issue_transition 
                    WHERE from_status_id=:f 
                    AND to_status_id=:t 
                    AND role_key=:r 
                    AND published=1',
                    ['f'=>$issue['status_id'],
                    't'=>$targetStatusId,'r'=>$role]
                );
                
                if(false===$rule)
                    throw new \DomainException('Transition not allowed.');
                
                if((bool)$rule['require_public_comment']&&''===trim((string)$comment))
                    throw new \DomainException('Public comment required.');
                
                $now=(new \DateTimeImmutable())->format('Y-m-d H:i:s');
                
                $changed=$db->executeStatement(
                    'UPDATE tl_issue 
                    SET status_id=:s,updated_at=:u,
                    version=version+1 
                    WHERE id=:id 
                    AND version=:v',
                    [
                        's'=>$targetStatusId,
                        'u'=>$now,'id'=>$issueId,
                        'v'=>$issue['version']
                    ]
                );
                
                if(1!==$changed)
                    throw new \RuntimeException('Concurrent modification.');
                
                if(null!==$comment&&''!==trim($comment))
                    $db->insert(
                        'tl_issue_comment',[
                            'tstamp'=>time(),
                            'issue_id'=>$issueId,
                            'visibility'=>'public',
                            'author_type'=>$actorType,
                            'author_id'=>$actorId,
                            'body'=>$comment,
                            'created_at'=>$now
                        ]
                    );
            
                    $db->insert(
                        'tl_issue_history',[
                            'issue_id'=>$issueId,
                            'event_type'=>'status_changed',
                            'actor_type'=>$actorType,
                            'actor_id'=>$actorId,
                            'old_value'=>json_encode(['status_id'=>(int)$issue['status_id']]),
                            'new_value'=>json_encode(['status_id'=>$targetStatusId]),
                            'created_at'=>$now
                        ]
                    );

                    $this->notifications->enqueue($issueId,'status_changed');
            }
        );
    } 
}
