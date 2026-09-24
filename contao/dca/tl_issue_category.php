<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_category'] = [
    'config' => [
        'dataContainer' => DC_Table::class, 
        'enableVersioning' => true, 
        'markAsCopy' => 'title',
        'sql' => [
            'keys' => [
                'id' => 'primary', 
                'service_id,alias' => 'unique', 
                'service_id' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['service_id', 'title'],
            'flag' => DataContainer::SORT_ASC,
        ],
        'label' => [
            'fields' => ['title', 'alias'],
            'format' => '%s [%s]',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
           'edit',
           'children',
           'copy',
           'cut',
           'delete',
           'toggle',
           'show',
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},service_id,title,alias,description;
                      {publish_legend},published',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int unsigned NOT NULL auto_increment'
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'service_id' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_issue_service.title',
            'eval' => ['mandatory' => true, 'chosen' => true],
            'filter' => true,
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'inputType' => 'text', 
            'eval' => ['mandatory' => true, 'maxlength' => 160], 
            'search' => true, 
            'sql' => "varchar(160) NOT NULL default ''"
        ],
        'alias' => [
            'inputType' => 'text', 
            'eval' => ['mandatory' => true, 'maxlength' => 160, 'rgxp' => 'alias', 'doNotCopy' => true], 
            'search' => true, 
            'sql' => "varchar(160) NOT NULL default ''"
        ],
        'description' => [
            'inputType' => 'textarea',
            'eval' => ['maxlength' => 255],
            'sql' => 'varchar(255) NULL'
        ],
        'published' => [
            'inputType' => 'checkbox', 
            'toggle' => true, 
            'filter' => true, 
            'sql' => "tinyint(1) NOT NULL default 1"
        ],
    ],
];
