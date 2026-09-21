<?php
declare(strict_types=1);
$GLOBALS['BE_MOD']['issue_service_management'] = [
 'issues' => ['tables'=>['tl_issue','tl_issue_comment','tl_issue_attachment']],
 'services' => ['tables'=>['tl_issue_service','tl_issue_category']],
 'workflow' => ['tables'=>['tl_issue_status','tl_issue_transition']],
 'settings' => ['tables'=>['tl_issue_settings']],
];
