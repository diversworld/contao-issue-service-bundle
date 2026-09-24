<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_notification'] = [
    'config' => ['dataContainer' => DC_Table::class, 
    'closed' => true, 'notCopyable' => true, 'notEditable' => true, 
    'sql' => ['keys' => ['id' => 'primary', 'event_uuid,recipient' => 'unique', 'status,next_attempt_at' => 'index']]],
    'fields' => [
        'id' => ['sql' => 'bigint unsigned NOT NULL auto_increment'],
        'event_uuid' => ['sql' => 'binary(16) NOT NULL'],
        'issue_id' => ['sql' => "bigint unsigned NOT NULL default 0"],
        'recipient' => ['sql' => "varchar(254) NOT NULL default ''"],
        'template_key' => ['sql' => "varchar(80) NOT NULL default ''"],
        'status' => ['sql' => "varchar(20) NOT NULL default 'pending'"],
        'attempt_count' => ['sql' => "smallint unsigned NOT NULL default 0"],
        'last_error' => ['sql' => 'longtext NULL'],
        'next_attempt_at' => ['sql' => 'datetime NULL'],
        'sent_at' => ['sql' => 'datetime NULL'],
        'created_at' => ['sql' => 'datetime NOT NULL'],
    ],
];