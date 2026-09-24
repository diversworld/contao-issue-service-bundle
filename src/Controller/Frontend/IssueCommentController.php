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
    public function __invoke(string $uuid,Request $r,IssueRepository $repo,CommentService $svc):Response
    {
        $u=$this->getUser();
        
        if(!$u instanceof FrontendUser)
            throw $this->createAccessDeniedException();
        
        $issue=$repo->findAuthorized($uuid,(int)$u->id,null);
        
        if(null===$issue)
            throw $this->createNotFoundException();
        
        $this->denyAccessUnlessGranted(IssueVoter::COMMENT,$issue);
        
        $f=$this->createForm(CommentType::class);
        $f->handleRequest($r);
        
        if($f->isSubmitted()&&$f->isValid())
            $svc->addPublic(
                (int)$issue['id'],'member',(int)$u->id,(string)$f->getData()['body']
            );

        return $this->redirectToRoute('issue_service_detail',['uuid'=>$uuid]);
    } 
}
