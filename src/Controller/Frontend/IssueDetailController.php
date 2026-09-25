<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Controller\Frontend;

use Contao\FrontendUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Diversworld\ContaoIssueServiceBundle\Form\CommentType;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Diversworld\ContaoIssueServiceBundle\Routing\IssueDetailUrlGenerator;
use Diversworld\ContaoIssueServiceBundle\Security\IssueVoter;

#[Route('/service/issues/{uuid}',name:'issue_service_detail',defaults:['_scope'=>'frontend'],requirements:['uuid'=>'[0-9a-fA-F-]{36}'],methods:['GET'])] 
final class IssueDetailController extends AbstractController 
{ 
    public function __invoke(string $uuid,IssueRepository $repo,\Contao\CoreBundle\Csrf\ContaoCsrfTokenManager $csrfTokenManager,IssueDetailUrlGenerator $detailUrlGenerator):Response
    {
        $u=$this->getUser();
        $member=$u instanceof FrontendUser?(int)$u->id:null;
        $issue=$repo->findAuthorized($uuid,$member,null);
        
        if(null===$issue)
            throw $this->createNotFoundException();
        
        $this->denyAccessUnlessGranted(IssueVoter::VIEW,$issue);

        $detailUrl = $detailUrlGenerator->generate($uuid);

        if (!str_starts_with($detailUrl, '/service/issues/')) {
            return new RedirectResponse($detailUrl);
        }
        
        return $this->render(
            '@ContaoIssueService/issue/detail.html.twig',
            [
                'issue'=>$issue,
                'attachments' => $repo->attachments((int) $issue['id']),
                'timeline'=>$repo->publicTimeline((int)$issue['id']),
                'commentForm'=>$this->createForm(CommentType::class, null, ['profile_id' => (int) ($issue['profile_id'] ?? 0), 'csrf_field_name' => 'REQUEST_TOKEN', 'csrf_token_manager' => $csrfTokenManager, 'csrf_token_id' => $this->getParameter('contao.csrf_token_name')])->createView()
            ]
        );
    } 
}
