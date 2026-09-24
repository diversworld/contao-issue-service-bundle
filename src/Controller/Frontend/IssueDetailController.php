<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Controller\Frontend;

use Contao\FrontendUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Diversworld\ContaoIssueServiceBundle\Form\CommentType;
use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Diversworld\ContaoIssueServiceBundle\Security\IssueVoter;

#[Route('/service/issues/{uuid}',name:'issue_service_detail',defaults:['_scope'=>'frontend'],requirements:['uuid'=>'[0-9a-fA-F-]{36}'],methods:['GET'])] 
final class IssueDetailController extends AbstractController 
{ 
    public function __invoke(string $uuid,IssueRepository $repo):Response
    {
        $u=$this->getUser();
        $member=$u instanceof FrontendUser?(int)$u->id:null;
        $issue=$repo->findAuthorized($uuid,$member,null);
        
        if(null===$issue)
            throw $this->createNotFoundException();
        
        $this->denyAccessUnlessGranted(IssueVoter::VIEW,$issue);
        
        return $this->render(
            '@ContaoIssueService/issue/detail.html.twig',
            [
                'issue'=>$issue,
                'timeline'=>$repo->publicTimeline((int)$issue['id']),
                'commentForm'=>$this->createForm(CommentType::class)->createView()
            ]
        );
    } 
}
