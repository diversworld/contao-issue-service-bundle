<?php

declare (strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla_level'] = [
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'notCopyable' => true,
        'notDeletable' => true,
        'enableVersioning' => false,
        'sql' => [
            'keys' => ['id' => 'primary', 'level_key' => 'unique'],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['id'],
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'operations' => ['edit', 'show'],
    ],
    'palettes' => ['default' => '{title_legend},title,level_key'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'title' => [
            'sql' => 'varchar(100) NOT NULL default \'\'',
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 100],
        ],
        'level_key' => [
            'sql' => 'varchar(16) NOT NULL default \'\'',
            'inputType' => 'select',
            'options' => [
                'bronze',
                'silver',
                'gold',
                'platinum',
            ],
            'eval' => ['mandatory' => true],
        ],
    ],
];
