<?php

declare (strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_license'] = [
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'notCopyable' => true,
        'notDeletable' => true,
        'enableVersioning' => false,
        'sql' => [
            'keys' => ['id' => 'primary'],
        ],
        'closed' => true,
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['id'],
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['id'],
            'format' => '%s',
        ],
        'operations' => ['edit', 'show'],
    ],
    'palettes' => ['default' => '{title_legend},token'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'token' => [
            'sql' => 'text NULL',
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true, 'preserveTags' => true],
        ],
    ],
];
$GLOBALS['TL_DCA']['tl_issue_license']['list']['global_operations']['validations'] = [
    'label' => ['Validierungen', ''],
    'href' => 'table=tl_issue_license_validation',
    'class' => 'header_all',
];
$GLOBALS['TL_DCA']['tl_issue_license']['fields']['license_status'] = [
    'input_field_callback' => [\Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\SlaCallbacks::class, 'licenseStatus'],
];
$GLOBALS['TL_DCA']['tl_issue_license']['palettes']['default'] = '{title_legend},license_status,token';
