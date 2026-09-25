<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_transition'] = [
	'config' => [
		'dataContainer' => DC_Table::class, 
		'enableVersioning' => true, 
		'sql' => [
			'keys' => [
				'id' => 'primary', 
				'from_status_id,to_status_id,role_key' => 'unique']
		]
	],
	'list' => [
		'sorting' => [
			'mode' => DataContainer::MODE_SORTED, 
			'fields' => ['from_status_id', 'to_status_id'], 
			'flag' => DataContainer::SORT_ASC, 
			'panelLayout' => 'filter;limit'
		], 
	'label' => [
		'fields' => ['from_status_id:tl_issue_status.title', 'to_status_id:tl_issue_status.title', 'role_key'], 
		'format' => '%s -> %s [%s]'
	], 
	'global_operations' => [
        'statuses' => [
            'label' => &$GLOBALS['TL_LANG']['tl_issue_transition']['statuses'],
            'href' => 'table=tl_issue_status',
            'icon' => 'back.svg',
        ],
    ],
	'operations' => [
		'edit',
		'children',
		'copy',
		'cut',
		'delete',
		'toggle',
		'show',
	]],
	'palettes' => [
		'default' => '{transition_legend},from_status_id,to_status_id,role_key;
					  {conditions_legend},require_public_comment;
					  {publish_legend},published'
	],
	'fields' => [
		'id' => [
			'sql' => 'int unsigned NOT NULL auto_increment'
		],
		'tstamp' => [
			'sql' => "int unsigned NOT NULL default 0"
		],
		'from_status_id' => [
			'inputType' => 'select', 
			'foreignKey' => 'tl_issue_status.title', 
			'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'], 
			'relation' => ['type' => 'hasOne', 'load' => 'eager'], 
			'sql' => "int unsigned NOT NULL default 0"
		],
		'to_status_id' => [
			'inputType' => 'select',
            'default' => null, 
			'foreignKey' => 'tl_issue_status.title', 
			'eval' => ['mandatory' => true, 'doNotCopy' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'], 
			'relation' => ['type' => 'hasOne', 'load' => 'eager'], 
			'sql' => "int unsigned NULL"
		],
		'role_key' => [
			'inputType' => 'select', 
			'options' => ['member', 'agent', 'manager'], 
			'reference' => &$GLOBALS['TL_LANG']['tl_issue_transition']['role_key_options'], 
			'filter' => true, 
			'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'], 
			'sql' => "varchar(40) NOT NULL default ''"
		],
		'require_public_comment' => [
			'inputType' => 'checkbox', 
			'eval' => ['tl_class' => 'clr'], 
			'sql' => "tinyint(1) NOT NULL default 0"
		],
		'published' => [
			'inputType' => 'checkbox', 
			'default' => 0,
            'eval' => ['doNotCopy' => true], 
			'toggle' => true, 
			'filter' => true, 
			'sql' => "tinyint(1) NOT NULL default 1"
		],
	],
];
