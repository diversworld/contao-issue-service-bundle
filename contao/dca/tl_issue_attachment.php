<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_attachment'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'notCopyable' => true,
        'notEditable' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'storage_key' => 'unique',
                'issue_id' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'fields' => ['created_at DESC']], 
    'label' => [
        'fields' => ['original_name', 'mime_type', 'file_size'], 
        'format' => '%s [%s, %s bytes]'
    ], 
        'operations' => [
            'edit',
            'children',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show'
        ]
    ],
    'fields' => [
        'id' => [
            'sql' => 'bigint unsigned NOT NULL auto_increment'
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'issue_id' => [
            'sql' => "bigint unsigned NOT NULL default 0"
        ],
        'comment_id' => [
            'sql' => 'bigint unsigned NULL'
        ],
        'storage_key' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'original_name' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'mime_type' => [
            'sql' => "varchar(120) NOT NULL default ''"
        ],
        'file_size' => [
            'sql' => "bigint unsigned NOT NULL default 0"
        ],
        'sha256' => [
            'sql' => "char(64) NOT NULL default ''"
        ],
        'uploaded_by_type' => [
            'sql' => "varchar(16) NOT NULL default 'system'"
        ],
        'uploaded_by_id' => [
            'sql' => 'int unsigned NULL'
        ],
        'scan_status' => [
            'sql' => "varchar(20) NOT NULL default 'not_scanned'"
        ],
        'created_at' => [
            'sql' => 'datetime NOT NULL'
        ],
    ],
];