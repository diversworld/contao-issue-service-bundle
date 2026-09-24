<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_history'] = [
    'config' => ['dataContainer' => DC_Table::class, 'closed' => true, 'notCopyable' => true, 'notEditable' => true, 'notDeletable' => true, 'sql' => ['keys' => ['id' => 'primary', 'issue_id,created_at' => 'index']]],
    'fields' => [
        'id' => ['sql' => 'bigint unsigned NOT NULL auto_increment'],
        'issue_id' => ['sql' => "bigint unsigned NOT NULL default 0"],
        'event_type' => ['sql' => "varchar(40) NOT NULL default ''"],
        'actor_type' => ['sql' => "varchar(16) NOT NULL default 'system'"],
        'actor_id' => ['sql' => 'int unsigned NULL'],
        'old_value' => ['sql' => 'json NULL'],
        'new_value' => ['sql' => 'json NULL'],
        'correlation_uuid' => ['sql' => 'binary(16) NULL'],
        'created_at' => ['sql' => 'datetime NOT NULL'],
    ],
];