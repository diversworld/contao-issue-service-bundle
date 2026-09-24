<?php

$GLOBALS['TL_LANG']['tl_issue_settings']['setting_legend'] = 'Setting';
$GLOBALS['TL_LANG']['tl_issue_settings']['new'] = ['New setting', 'Create a new setting.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['edit'] = ['Edit setting', 'Edit setting ID %s.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['delete'] = ['Delete setting', 'Delete setting ID %s.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['show'] = ['Setting details', 'Show details of setting ID %s.'];

$GLOBALS['TL_LANG']['tl_issue_settings']['setting_key'] = ['Setting', 'Select the setting to edit.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value'] = ['Value', 'Set the value.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['value_type'] = ['Value type', 'Internal type of the setting.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_require_login'] = ['Require login', 'Only logged-in members may create issues.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_ticket_pattern'] = ['Ticket number pattern', 'Available placeholders: {SERVICE}, {YEAR}, {SEQ}.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_allowed_extensions'] = ['Allowed file types', 'Select the allowed file extensions for attachments.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_max_file_size'] = ['Maximum file size', 'Maximum file size in bytes.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_max_files_per_issue'] = ['Maximum attachments per issue', 'Maximum number of attachments per issue.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_retention_policy'] = ['Closed issue retention', 'Number of days after which closed issues are considered by cleanup.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_reopen_roles'] = ['Roles allowed to reopen', 'Select which roles may reopen an issue.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_mail_recipients'] = ['Notification recipients', 'Enter one e-mail address per line.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_service_scoped_permissions'] = ['Service-scoped permissions', 'Restrict permissions to assigned services.'];

$GLOBALS['TL_LANG']['tl_issue_settings']['setting_key_options'] = [
    'require_login' => ['Require login'],
    'ticket_pattern' => ['Ticket number pattern'],
    'allowed_extensions' => ['Allowed file types'],
    'max_file_size' => ['Maximum file size'],
    'max_files_per_issue' => ['Maximum attachments per issue'],
    'retention_policy' => ['Closed issue retention'],
    'reopen_roles' => ['Roles allowed to reopen'],
    'mail_recipients' => ['Notification recipients'],
    'service_scoped_permissions' => ['Service-scoped permissions'],
];

$GLOBALS['TL_LANG']['tl_issue_settings']['role_options'] = [
    'member' => 'Member',
    'agent' => 'Agent',
    'manager' => 'Manager',
];