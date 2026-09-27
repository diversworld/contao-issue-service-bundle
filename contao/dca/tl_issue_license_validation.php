<?php

declare (strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_license_validation'] = [
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'notCopyable' => true,
        'notDeletable' => true,
        'enableVersioning' => false,
        'sql' => [
            'keys' => ['id' => 'primary'],
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
            'fields' => ['result', 'created_at'],
            'format' => '%s',
        ],
        'operations' => ['show'],
    ],
    'palettes' => ['default' => '{title_legend},license_id,result,created_at'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'license_id' => [
            'sql' => 'int NOT NULL default 1',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
        ],
        'result' => [
            'sql' => 'varchar(32) NOT NULL default \'\'',
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 32],
        ],
        'created_at' => [
            'sql' => 'int NOT NULL default 0',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
        ],
    ],
];
