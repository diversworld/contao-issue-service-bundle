<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Controller\Backend;

use Contao\CoreBundle\Controller\Backend\BackendController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Diversworld\ContaoIssueServiceBundle\Application\SettingsService;

#[Route('%contao.backend.route_prefix%/issue-service',name:'issue_service_backend',defaults:['_scope'=>'backend'],methods:['GET'])] 
final class IssueDashboardController extends BackendController 
{ 
    public function __invoke(SettingsService $settings):Response
    {
        return $this->render('@ContaoIssueService/backend/dashboard.html.twig',[
            'headline'=>'Issue & Service Management',
            'setupComplete'=>$settings->isComplete()
            ]
        );
    } 
}
