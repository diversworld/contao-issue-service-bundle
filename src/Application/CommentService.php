<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Application;

use Diversworld\ContaoIssueServiceBundle\Repository\CommentRepository;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;

final class CommentService 
{ 
    public function __construct(private readonly CommentRepository $comments,private readonly IssueRepository $issues,private readonly NotificationService $notifications)
    {} 
    
    public function addPublic(int $issueId,string $authorType,?int $authorId,string $body):int
    {
        return $this->add($issueId,'public',$authorType,$authorId,$body,true);
    } 
    
    public function addInternal(int $issueId,int $userId,string $body):int
    {
        return $this->add($issueId,'internal','user',$userId,$body,false);
    } 
    
    private function add(int $issueId,string $visibility,string $authorType,?int $authorId,string $body,bool $notify):int
    {
        if(''===trim($body))
            throw new \InvalidArgumentException('Empty comment.');
        
        $db=$this->issues->connection();
        
        return $db->transactional(
            function()use(
                $issueId,
                $visibility,
                $authorType,
                $authorId,
                $body,
                $notify,
                $db
                ){
                    $id=$this->comments->add(
                        $issueId,
                        $visibility,
                        $authorType,
                        $authorId,
                        $body
                    );
                    
                    $now=(new \DateTimeImmutable())->format('Y-m-d H:i:s');
                    
                    $db->insert(
                        'tl_issue_history',
                        [
                            'issue_id' => $issueId,
                            'event_type' => $visibility.'_comment_added',
                            'actor_type' => $authorType,
                            'actor_id' => $authorId,
                            'new_value' => json_encode(['comment_id' => $id]),
                            'created_at' => $now
                        ]
                    );
                    
                    if($notify){
                        $db->update(
                            'tl_issue',
                            ['last_public_activity_at' => $now, 'updated_at' => $now],
                            ['id' => $issueId]
                        );
                        $this->notifications->enqueue($issueId,'public_comment_added');
                    }
                    
                    return $id;
                }
            );
    }
}
