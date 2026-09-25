<?php

$GLOBALS['TL_LANG']['tl_issue_transition']['transition_legend'] = 'Transition';

$GLOBALS['TL_LANG']['tl_issue_transition']['new'] = ['New transition', 'Create a new workflow rule.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['edit'] = ['Edit transition', 'Edit transition ID %s.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['delete'] = ['Delete transition', 'Delete transition ID %s.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['show'] = ['Transition details', 'Show details of transition ID %s.'];

$GLOBALS['TL_LANG']['tl_issue_transition']['from_status_id'] = ['From status', 'Source status of the transition.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['to_status_id'] = ['To status', 'Target status of the transition.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['role_key'] = ['Role', 'Role that is allowed to execute this transition.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['require_public_comment'] = ['Require public comment', 'A public comment must be entered for this transition.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['published'] = ['Published', 'Make the transition available in the workflow.'];

$GLOBALS['TL_LANG']['tl_issue_transition']['role_key_options'] = [
    'member' => 'Member',
    'agent' => 'Agent',
    'manager' => 'Manager',
];
$GLOBALS['TL_LANG']['tl_issue_transition']['statuses'] = ['Manage statuses', 'Edit the available ticket statuses.'];

$GLOBALS['TL_LANG']['tl_issue_transition']['conditions_legend'] = 'Conditions';

$GLOBALS['TL_LANG']['tl_issue_transition']['publish_legend'] = 'Activation';

$GLOBALS['TL_LANG']['tl_issue_transition']['sameStatus'] = 'Source and target status must be different.';

$GLOBALS['TL_LANG']['tl_issue_transition']['duplicateRule'] = 'A rule already exists for this source status, target status and role.';
