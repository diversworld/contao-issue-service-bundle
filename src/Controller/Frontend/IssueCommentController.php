<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Controller\Frontend;

use Contao\FrontendUser;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Diversworld\ContaoIssueServiceBundle\Application\{AttachmentService, CommentService, IssueWorkflowService, SettingsService};
use Diversworld\ContaoIssueServiceBundle\Form\CommentType;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Diversworld\ContaoIssueServiceBundle\Security\IssueVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/service/issues/{uuid}/comment', name: 'issue_service_comment', defaults: ['_scope' => 'frontend'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
final class IssueCommentController extends AbstractController
{
    public function __invoke(string $uuid, Request $request, IssueRepository $repo, ContaoCsrfTokenManager $csrf, CommentService $comments, AttachmentService $files, SettingsService $settings, IssueWorkflowService $workflow): Response
    {
        $user = $this->getUser();
        if (!$user instanceof FrontendUser) throw $this->createAccessDeniedException();
        $issue = $repo->findAuthorized($uuid, (int) $user->id, null);
        if (!$issue) throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted(IssueVoter::COMMENT, $issue);
        $settings = $settings->forProfile((int) ($issue['profile_id'] ?? 0));
        $form = $this->createForm(CommentType::class, null, [
            'profile_id' => (int) ($issue['profile_id'] ?? 0), 'status_choices' => $workflow->choices($issue),
            'csrf_field_name' => 'REQUEST_TOKEN', 'csrf_token_manager' => $csrf, 'csrf_token_id' => $this->getParameter('contao.csrf_token_name'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $db = $repo->connection();
            try {
                $db->transactional(function () use ($db, $form, $issue, $user, $comments, $files, $settings, $workflow): void {
                    $db->fetchOne('SELECT id FROM tl_issue WHERE id=:id FOR UPDATE', ['id' => $issue['id']]);
                    $uploads = $form->get('attachments')->getData() ?? [];
                    $count = (int) $db->fetchOne('SELECT COUNT(*) FROM tl_issue_attachment WHERE issue_id=:id', ['id' => $issue['id']]);
                    if ($uploads && $count + count($uploads) > $settings->int('max_files_per_issue', 5)) {
                        $form->get('attachments')->addError(new FormError('Die maximale Anzahl der Anhänge für dieses Ticket ist erreicht.'));
                        return;
                    }
                    $directory = $issue['attachment_directory'] ?? null;
                    if ($directory === null) {
                        $key = $db->fetchOne('SELECT storage_key FROM tl_issue_attachment WHERE issue_id=:id ORDER BY id LIMIT 1', ['id' => $issue['id']]);
                        $directory = $key && str_contains($key, '/') ? substr($key, 0, strrpos($key, '/')) : '';
                    }
                    $body = trim((string) $form->get('body')->getData());
                    $target = $form->has('targetStatus') ? (int) $form->get('targetStatus')->getData() : 0;
                    if ($target) {
                        $commentId = $workflow->transition((int) $issue['id'], $target, $body, (int) $issue['status_id']);
                        if ($uploads && $commentId === null) $commentId = $comments->addPublic((int) $issue['id'], 'member', (int) $user->id, 'Anhänge ergänzt.');
                    } else {
                        $commentId = $comments->addPublic((int) $issue['id'], 'member', (int) $user->id, $body !== '' ? $body : 'Anhänge ergänzt.');
                    }
                    foreach ($uploads as $upload) $files->upload((int) $issue['id'], $upload, 'member', (int) $user->id, $commentId, $directory);
                });
            } catch (\DomainException $error) {
                $form->addError(new FormError($error->getMessage()));
            }
            if ($form->isValid()) return $this->redirectToRoute('issue_service_detail', ['uuid' => $uuid]);
        }
        return $this->render('@ContaoIssueService/issue/detail.html.twig', [
            'issue' => $issue, 'attachments' => $repo->attachments((int) $issue['id']),
            'timeline' => $repo->publicTimeline((int) $issue['id']), 'commentForm' => $form->createView(),
        ], new Response(status: 422, headers: ['Cache-Control' => 'private, no-store']));
    }
}
