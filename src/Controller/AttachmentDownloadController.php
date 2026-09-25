<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Diversworld\ContaoIssueServiceBundle\Application\AttachmentService;
use Diversworld\ContaoIssueServiceBundle\Security\AttachmentVoter;

#[Route('/service/issues/{uuid}/attachment/{id}',name:'issue_service_attachment',defaults:['_scope'=>'frontend'],methods:['GET'])] 
final class AttachmentDownloadController extends AbstractController 
{ 
    public function __invoke(string $uuid,int $id,Connection $db,AttachmentService $files):BinaryFileResponse
    {
        $row=$db->fetchAssociative(
            "SELECT a.*,i.member_id 
            FROM tl_issue_attachment a 
            JOIN tl_issue i ON i.id=a.issue_id
            LEFT JOIN tl_issue_comment c ON c.id=a.comment_id AND c.issue_id=a.issue_id
            WHERE i.deleted_at IS NULL AND (a.comment_id IS NULL OR c.visibility='public') AND a.id=:id AND HEX(i.uuid)=REPLACE(UPPER(:uuid),'-','')",
            ['id'=>$id,'uuid'=>$uuid]
        );
        
        if(false===$row)
            throw $this->createNotFoundException();
        
        $this->denyAccessUnlessGranted(AttachmentVoter::DOWNLOAD,$row);
        
        if (!is_file($files->path($row['storage_key']))) {
            throw $this->createNotFoundException('Attachment file not found.');
        }
        $r=new BinaryFileResponse($files->path($row['storage_key']));
        
        $r->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$row['original_name']);
        $r->headers->set('Content-Type','application/octet-stream');
        $r->headers->set('X-Content-Type-Options','nosniff');
        
        $r->setPrivate();
        $r->headers->set('Cache-Control', 'private, no-store');
        return $r;
    } 
}
