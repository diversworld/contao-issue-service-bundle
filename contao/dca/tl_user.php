<?php

declare(strict_types=1);

foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $name => &$palette) {
    if (is_string($palette)) $palette .= ';{issue_notifications_legend},issue_notify_issue_created,issue_notify_assignment_changed,issue_notify_status_changed,issue_notify_public_comment_added';
}
unset($palette);

$GLOBALS['TL_DCA']['tl_user']['fields']['issue_notify_issue_created'] = [
    'inputType' => 'checkbox', 'default' => '1',
    'eval' => ['tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_user']['fields']['issue_notify_assignment_changed'] = [
    'inputType' => 'checkbox', 'default' => '1',
    'eval' => ['tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_user']['fields']['issue_notify_status_changed'] = [
    'inputType' => 'checkbox', 'default' => '1',
    'eval' => ['tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_user']['fields']['issue_notify_public_comment_added'] = [
    'inputType' => 'checkbox', 'default' => '1',
    'eval' => ['tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default '1'",
];
