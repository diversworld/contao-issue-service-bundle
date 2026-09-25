<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Controller\Frontend;

use Contao\FrontendUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Diversworld\ContaoIssueServiceBundle\Application\CommentService;
use Diversworld\ContaoIssueServiceBundle\Form\CommentType;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Diversworld\ContaoIssueServiceBundle\Security\IssueVoter;

#[Route('/service/issues/{uuid}/comment',name:'issue_service_comment',defaults:['_scope'=>'frontend'],requirements:['uuid'=>'[0-9a-fA-F-]{36}'],methods:['POST'])]
final class IssueCommentController extends AbstractController 
{ 
    public function __invoke(string $uuid,Request $r,IssueRepository $repo,\Contao\CoreBundle\Csrf\ContaoCsrfTokenManager $csrfTokenManager,CommentService $svc, \Diversworld\ContaoIssueServiceBundle\Application\AttachmentService $files, \Diversworld\ContaoIssueServiceBundle\Application\SettingsService $settings):Response
    {
        $u=$this->getUser();
        
        if(!$u instanceof FrontendUser)
            throw $this->createAccessDeniedException();
        
        $issue=$repo->findAuthorized($uuid,(int)$u->id,null);
        
        if(null===$issue)
            throw $this->createNotFoundException();
        
        $this->denyAccessUnlessGranted(IssueVoter::COMMENT,$issue);
        
        $settings = $settings->forProfile((int) ($issue['profile_id'] ?? 0));
        $f=$this->createForm(CommentType::class, null, [
            'profile_id' => (int) ($issue['profile_id'] ?? 0),
            'csrf_field_name' => 'REQUEST_TOKEN',
            'csrf_token_manager' => $csrfTokenManager,
            'csrf_token_id' => $this->getParameter('contao.csrf_token_name'),
        ]);
        $f->handleRequest($r);
        
        if ($f->isSubmitted() && $f->isValid()) {
            $db = $repo->connection();
            $db->transactional(function () use ($db, $f, $issue, $u, $files, $svc, $settings): void {
                $db->fetchOne('SELECT id FROM tl_issue WHERE id=:id FOR UPDATE', ['id' => $issue['id']]);
                $uploads = $f->get('attachments')->getData() ?? [];
                $count = (int) $db->fetchOne('SELECT COUNT(*) FROM tl_issue_attachment WHERE issue_id=:id', ['id' => $issue['id']]);
                if ($uploads && $count + count($uploads) > $settings->int('max_files_per_issue', 5)) {
                    $f->get('attachments')->addError(new \Symfony\Component\Form\FormError('Die maximale Anzahl der Anhänge für dieses Ticket ist erreicht.'));
                    return;
                }
                $directory = $issue['attachment_directory'] ?? null;
                if ($directory === null) {
                    $key = $db->fetchOne('SELECT storage_key FROM tl_issue_attachment WHERE issue_id=:id ORDER BY id LIMIT 1', ['id' => $issue['id']]);
                    $directory = $key && str_contains($key, '/') ? substr($key, 0, strrpos($key, '/')) : '';
                }
                $body = trim((string) $f->get('body')->getData());
                $commentId = $svc->addPublic((int) $issue['id'], 'member', (int) $u->id, $body !== '' ? $body : 'Anhänge ergänzt.');
                foreach ($uploads as $upload) {
                    $files->upload((int) $issue['id'], $upload, 'member', (int) $u->id, $commentId, $directory);
                }
            });
        }

        if (!$f->isSubmitted() || !$f->isValid()) {
            return $this->render('@ContaoIssueService/issue/detail.html.twig', [
                'issue' => $issue,
                'attachments' => $repo->attachments((int) $issue['id']),
                'timeline' => $repo->publicTimeline((int) $issue['id']),
                'commentForm' => $f->createView(),
            ], new Response(status: 422));
        }

        return $this->redirectToRoute('issue_service_detail',['uuid'=>$uuid]);
    } 
}
