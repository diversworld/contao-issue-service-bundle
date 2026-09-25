<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Controller\Frontend;

use Contao\FrontendUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Diversworld\ContaoIssueServiceBundle\Application\AttachmentService;
use Diversworld\ContaoIssueServiceBundle\Application\IssueApplicationService;
use Diversworld\ContaoIssueServiceBundle\Domain\Dto\CreateIssueCommand;
use Diversworld\ContaoIssueServiceBundle\Form\IssueCreateType;

#[Route('/service/issues/new',name:'issue_service_create',defaults:['_scope'=>'frontend'],methods:['GET','POST'])] 
final class IssueCreateController extends AbstractController 
{ 
    public function __invoke(Request $r,IssueApplicationService $svc,AttachmentService $attachments):Response
    {
        $u=$this->getUser();
        
        if(!$u instanceof FrontendUser)
            throw $this->createAccessDeniedException();
        
        $f=$this->createForm(IssueCreateType::class);
        $f->handleRequest($r);
        
        if($f->isSubmitted()&&$f->isValid())
        {
            $d=$f->getData();
            $x=$svc->create(
                new CreateIssueCommand(
                    (int)$u->id,
                    (int)$d['serviceId'],
                    $d['categoryId']? (int)$d['categoryId']:null,
                    (string)$d['type'],
                    trim((string)$d['title']),
                    trim((string)$d['description']),
                    priority: (string)$d['priority']
                )
            );
                
            foreach($f->get('attachments')->getData()??[] as $file)$attachments->upload($x['id'],$file,'member',(int)$u->id);
                
            return $this->redirectToRoute('issue_service_detail',['uuid'=>$x['uuid']]);
        }
                
        return $this->render('@ContaoIssueService/issue/create.html.twig',['form'=>$f->createView()]);
    } 
}
