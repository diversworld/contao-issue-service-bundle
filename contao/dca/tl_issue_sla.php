<?php

declare (strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla'] = [
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'notCopyable' => true,
        'notDeletable' => true,
        'enableVersioning' => false,
        'sql' => [
            'keys' => ['id' => 'primary'],
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
    'palettes' => ['default' => '{title_legend},title,level_id,response_minutes,resolve_minutes,timezone,business_hours,holidays,published'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'title' => [
            'sql' => 'varchar(160) NOT NULL default \'\'',
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 160],
        ],
        'level_id' => [
            'sql' => 'int unsigned NOT NULL default 0',
            'inputType' => 'select',
            'foreignKey' => 'tl_issue_sla_level.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
        ],
        'response_minutes' => [
            'sql' => 'int NOT NULL default 60',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
        ],
        'resolve_minutes' => [
            'sql' => 'int NOT NULL default 480',
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'digit',
                'mandatory' => true,
                'minval' => 0,
            ],
        ],
        'timezone' => [
            'sql' => 'varchar(64) NOT NULL default \'Europe/Berlin\'',
            'inputType' => 'text',
            'default' => 'Europe/Berlin',
        ],
        'business_hours' => [
            'sql' => 'text NULL',
            'inputType' => 'textarea',
            'default' => '{"1":[["09:00","17:00"]],"2":[["09:00","17:00"]],"3":[["09:00","17:00"]],"4":[["09:00","17:00"]],"5":[["09:00","17:00"]]}',
            'eval' => ['mandatory' => true],
        ],
        'holidays' => [
            'sql' => 'text NULL',
            'inputType' => 'textarea',
            'default' => '[]',
        ],
        'published' => ['sql' => 'tinyint(1) NOT NULL default 0', 'inputType' => 'checkbox'],
    ],
];
$GLOBALS['TL_DCA']['tl_issue_sla']['list']['global_operations']['levels'] = [
    'label' => ['SLA-Stufen', ''],
    'href' => 'table=tl_issue_sla_level',
    'class' => 'header_all',
];
$GLOBALS['TL_DCA']['tl_issue_sla']['list']['global_operations']['escalations'] = [
    'label' => ['Eskalationen', ''],
    'href' => 'table=tl_issue_sla_escalation',
    'class' => 'header_all',
];
$GLOBALS['TL_DCA']['tl_issue_sla']['list']['global_operations']['history'] = [
    'label' => ['SLA-Historie', ''],
    'href' => 'table=tl_issue_sla_history',
    'class' => 'header_all',
];
