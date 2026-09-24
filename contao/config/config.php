<?php

declare(strict_types=1);

use Diversworld\ContaoIssueServiceBundle\Model\IssueModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueServiceModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueCategoryModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueCommentModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueAttachmentModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueHistoryModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueStatusModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueTransitionModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueSettingsModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueServiceGroupModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueNotificationModel;
use Diversworld\ContaoIssueServiceBundle\Model\IssueSequenceModel;
use Diversworld\ContaoIssueServiceBundle\Backend\IssueDashboardModule;
use Diversworld\ContaoIssueServiceBundle\FrontendModule\IssueCreateModule;
use Diversworld\ContaoIssueServiceBundle\FrontendModule\IssueDetailModule;
use Diversworld\ContaoIssueServiceBundle\FrontendModule\IssueListModule;

$GLOBALS['BE_MOD']['issue_service_management'] = [

    'issue_service_dashboard' => [
        'callback' => IssueDashboardModule::class,
    ],

    'issue_service_issues' => [
        'tables'=>[
            'tl_issue','tl_issue_comment','tl_issue_attachment'
        ]
    ],
    'issue_service_services' => [
        'tables'=>[
            'tl_issue_service'
        ]
    ],
    'issue_service_categories' => [
        'tables'=>[
            'tl_issue_category'
        ]
    ],
    'issue_service_workflow' => [
        'tables'=>[
            'tl_issue_status','tl_issue_transition'
        ]
    ],
    'issue_service_settings' => [
        'tables'=>[
            'tl_issue_settings'
        ]
    ],
];

/* Backend Module */

$GLOBALS['TL_MODELS']['tl_issue'] = IssueModel::class;
$GLOBALS['TL_MODELS']['tl_issue_service'] = IssueServiceModel::class;
$GLOBALS['TL_MODELS']['tl_issue_category'] = IssueCategoryModel::class;
$GLOBALS['TL_MODELS']['tl_issue_comment'] = IssueCommentModel::class;
$GLOBALS['TL_MODELS']['tl_issue_attachment'] = IssueAttachmentModel::class;
$GLOBALS['TL_MODELS']['tl_issue_history'] = IssueHistoryModel::class;
$GLOBALS['TL_MODELS']['tl_issue_status'] = IssueStatusModel::class;
$GLOBALS['TL_MODELS']['tl_issue_transition'] = IssueTransitionModel::class;
$GLOBALS['TL_MODELS']['tl_issue_settings'] = IssueSettingsModel::class;
$GLOBALS['TL_MODELS']['tl_issue_service_group'] = IssueServiceGroupModel::class;
$GLOBALS['TL_MODELS']['tl_issue_notification'] = IssueNotificationModel::class;
$GLOBALS['TL_MODELS']['tl_issue_sequence'] = IssueSequenceModel::class;

/* Frontend Module*/

$GLOBALS['FE_MOD']['issue_service'] = [
    'issue_service_list' => IssueListModule::class,
    'issue_service_create' => IssueCreateModule::class,
    'issue_service_detail' => IssueDetailModule::class,
];