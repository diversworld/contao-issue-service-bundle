<?php

$GLOBALS['TL_LANG']['tl_issue']['issue_legend'] = 'Issue';
$GLOBALS['TL_LANG']['tl_issue']['processing_legend'] = 'Processing';
$GLOBALS['TL_LANG']['tl_issue']['time_legend'] = 'Timestamps';

$GLOBALS['TL_LANG']['tl_issue']['new'] = ['New issue', 'Create a new issue.'];
$GLOBALS['TL_LANG']['tl_issue']['edit'] = ['Edit issue', 'Edit issue ID %s.'];
$GLOBALS['TL_LANG']['tl_issue']['show'] = ['Issue details', 'Show details of issue ID %s.'];

$GLOBALS['TL_LANG']['tl_issue']['ticket_number'] = ['Ticket number', 'The automatically assigned unique ticket number.'];
$GLOBALS['TL_LANG']['tl_issue']['title'] = ['Title', 'Enter a short, descriptive title.'];
$GLOBALS['TL_LANG']['tl_issue']['issue_type'] = ['Type', 'Select the request type.'];
$GLOBALS['TL_LANG']['tl_issue']['service_id'] = ['Service', 'Select the affected service.'];
$GLOBALS['TL_LANG']['tl_issue']['category_id'] = ['Category', 'Optionally select a category.'];
$GLOBALS['TL_LANG']['tl_issue']['description'] = ['Description', 'Describe the issue as precisely as possible.'];
$GLOBALS['TL_LANG']['tl_issue']['status_id'] = ['Status', 'Select the current workflow status.'];
$GLOBALS['TL_LANG']['tl_issue']['priority'] = ['Priority', 'Select the processing priority.'];
$GLOBALS['TL_LANG']['tl_issue']['assigned_user_id'] = ['Assigned user', 'Optionally assign the issue to a back end user.'];
$GLOBALS['TL_LANG']['tl_issue']['resolution'] = ['Resolution', 'Document the resolution or closing note.'];
$GLOBALS['TL_LANG']['tl_issue']['created_at'] = ['Created at', 'Creation date and time.'];
$GLOBALS['TL_LANG']['tl_issue']['updated_at'] = ['Updated at', 'Last update date and time.'];
$GLOBALS['TL_LANG']['tl_issue']['last_public_activity_at'] = ['Last public activity', 'Date and time of the last public activity.'];
$GLOBALS['TL_LANG']['tl_issue']['resolved_at'] = ['Resolved at', 'Resolution date and time.'];
$GLOBALS['TL_LANG']['tl_issue']['closed_at'] = ['Closed at', 'Closing date and time.'];
$GLOBALS['TL_LANG']['tl_issue']['deleted_at'] = ['Deleted at', 'Soft deletion date and time.'];

$GLOBALS['TL_LANG']['tl_issue']['issue_type_options'] = [
    'incident' => 'Incident',
    'bug' => 'Bug',
    'improvement' => 'Improvement',
    'request' => 'Request',
    'question' => 'Question',
];

$GLOBALS['TL_LANG']['tl_issue']['priority_options'] = [
    'low' => 'Low',
    'normal' => 'Normal',
    'high' => 'High',
    'critical' => 'Critical',
];