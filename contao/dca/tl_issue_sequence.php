<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_sequence'] = [
    'config' => ['dataContainer' => DC_Table::class, 'closed' => true, 'notCopyable' => true, 'notEditable' => true, 'notDeletable' => true, 'sql' => ['keys' => ['sequence_key' => 'primary']]],
    'fields' => [
        'sequence_key' => ['sql' => "varchar(100) NOT NULL default ''"],
        'current_value' => ['sql' => "bigint unsigned NOT NULL default 0"],
    ],
];