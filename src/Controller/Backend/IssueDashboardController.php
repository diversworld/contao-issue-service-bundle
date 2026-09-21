<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\Controller\Backend;
use Contao\CoreBundle\Controller\AbstractBackendController;use Symfony\Component\HttpFoundation\Response;use Symfony\Component\Routing\Attribute\Route;use Vendor\ContaoIssueServiceBundle\Application\SettingsService;
#[Route('%contao.backend.route_prefix%/issue-service',name:'issue_service_backend',defaults:['_scope'=>'backend'],methods:['GET'])] final class IssueDashboardController extends AbstractBackendController { public function __invoke(SettingsService $settings):Response{return $this->render('@ContaoIssueService/backend/dashboard.html.twig',['headline'=>'Issue & Service Management','setupComplete'=>$settings->isComplete()]);} }
