<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_service_group'] = [
    'config' => ['dataContainer' => DC_Table::class, 'closed' => true, 'sql' => ['keys' => ['service_id,user_group_id,role_key' => 'primary']]],
    'fields' => [
        'service_id' => ['sql' => "int unsigned NOT NULL default 0"],
        'user_group_id' => ['sql' => "int unsigned NOT NULL default 0"],
        'role_key' => ['sql' => "varchar(40) NOT NULL default 'agent'"],
    ],
];