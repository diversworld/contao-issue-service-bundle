<?php

declare (strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla_history'] = [
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'notCopyable' => true,
        'notDeletable' => true,
        'enableVersioning' => false,
        'sql' => [
            'keys' => ['id' => 'primary', 'issue_id,created_at' => 'index'],
        ],
        'closed' => true,
        'notEditable' => true,
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['id'],
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => [
                'issue_id',
                'event_type',
                'created_at',
            ],
            'format' => '%s',
        ],
        'operations' => ['show'],
    ],
    'palettes' => ['default' => '{title_legend},issue_id,event_type,details,created_at,actor_id,previous_hash,entry_hash'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'issue_id' => [
            'sql' => 'int NOT NULL default 0',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
            'filter' => true,
        ],
        'event_type' => [
            'sql' => 'varchar(40) NOT NULL default \'\'',
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 40],
        ],
        'details' => ['sql' => 'longtext NULL', 'inputType' => 'textarea'],
        'created_at' => [
            'sql' => 'int NOT NULL default 0',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
        ],
        'actor_id' => [
            'sql' => 'int NOT NULL default 0',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
        ],
        'previous_hash' => [
            'sql' => 'varchar(64) NOT NULL default \'\'',
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 64],
        ],
        'entry_hash' => [
            'sql' => 'varchar(64) NOT NULL default \'\'',
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 64],
        ],
    ],
];
