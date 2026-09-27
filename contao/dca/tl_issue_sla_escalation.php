<?php

declare (strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla_escalation'] = [
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'notCopyable' => true,
        'notDeletable' => true,
        'enableVersioning' => false,
        'sql' => [
            'keys' => ['id' => 'primary', 'sla_id,stage' => 'unique'],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['id'],
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['sla_id', 'stage'],
            'format' => '%s',
        ],
        'operations' => ['edit', 'show'],
    ],
    'palettes' => ['default' => '{title_legend},sla_id,stage,delay_minutes,recipients,published'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'sla_id' => [
            'sql' => 'int unsigned NOT NULL default 0',
            'inputType' => 'select',
            'foreignKey' => 'tl_issue_sla.title',
            'eval' => ['mandatory' => true],
        ],
        'stage' => [
            'sql' => 'int NOT NULL default 1',
            'inputType' => 'select',
            'options' => [
                1,
                2,
                3,
            ],
            'reference' => [
                1 => 'Teamleiter',
                2 => 'Service Manager',
                3 => 'Administrator',
            ],
        ],
        'delay_minutes' => [
            'sql' => 'int NOT NULL default 0',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
        ],
        'recipients' => [
            'sql' => 'text NULL',
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true],
        ],
        'published' => ['sql' => 'tinyint(1) NOT NULL default 0', 'inputType' => 'checkbox'],
    ],
];
