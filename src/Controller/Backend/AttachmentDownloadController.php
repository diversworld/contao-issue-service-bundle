<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Controller\Backend;

use Contao\BackendUser;
use Diversworld\ContaoIssueServiceBundle\Application\AttachmentService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('%contao.backend.route_prefix%/issue-attachment/{id}', name: 'issue_service_backend_attachment', defaults: ['_scope' => 'backend'], requirements: ['id' => '\d+'], methods: ['GET'])]
final class AttachmentDownloadController extends AbstractController
{
    public function __invoke(int $id, Connection $db, AttachmentService $files): BinaryFileResponse
    {
        $user = $this->getUser();
        if (!$user instanceof BackendUser || (!$user->isAdmin && !$user->hasAccess('issue_service_issues', 'modules'))) {
            throw $this->createAccessDeniedException();
        }
        $row = $db->fetchAssociative('SELECT a.* FROM tl_issue_attachment a JOIN tl_issue i ON i.id=a.issue_id WHERE a.id=:id AND i.deleted_at IS NULL', ['id' => $id]);
        if (!$row || !is_file($files->path($row['storage_key']))) {
            throw $this->createNotFoundException();
        }
        $response = new BinaryFileResponse($files->path($row['storage_key']));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $row['original_name']);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
