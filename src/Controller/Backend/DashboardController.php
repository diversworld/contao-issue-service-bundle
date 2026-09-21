<?php
declare(strict_types=1); namespace Vendor\ContaoIssueServiceBundle\Controller\Backend; use Contao\CoreBundle\Controller\AbstractBackendController; use Symfony\Component\HttpFoundation\Response; use Symfony\Component\Routing\Attribute\Route;
#[Route('%contao.backend.route_prefix%/issue-service',name:self::class,defaults:['_scope'=>'backend'],methods:['GET'])] final class DashboardController extends AbstractBackendController { public function __invoke():Response{return $this->render('@ContaoIssueService/backend/dashboard.html.twig',['headline'=>'Issue & Service Management']);} }
