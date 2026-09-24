<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\IssueSettingsDcaCallbacks;

$GLOBALS['TL_DCA']['tl_issue_settings'] = [
	'config' => [
		'dataContainer' => DC_Table::class, 
		'enableVersioning' => true, 
		'onload_callback' => [[IssueSettingsDcaCallbacks::class, 'configureValueField']], 
		'onsubmit_callback' => [[IssueSettingsDcaCallbacks::class, 'updateMetadata']], 
		'sql' => [
			'keys' => [
				'id' => 'primary', 
				'setting_key' => 'unique'
			]
		]
	],
	'list' => [
		'sorting' => [
			'mode' => DataContainer::MODE_SORTED, 
			'fields' => ['setting_key'], 
			'flag' => DataContainer::SORT_ASC], 
			'label' => [
				'fields' => ['setting_key', 'setting_value'], 
				'label_callback' => [IssueSettingsDcaCallbacks::class, 'formatLabel']
			], 
			'operations' => [
				'edit', 
				'delete', 
				'show'
			]
		],
	'palettes' => ['default' => '{setting_legend},setting_key,setting_value'],
	'fields' => [
		'id' => [
			'sql' => 'int unsigned NOT NULL auto_increment'],
		'tstamp' => [
			'sql' => "int unsigned NOT NULL default 0"],
		'setting_key' => [
			'inputType' => 'select', 
			'options' => IssueSettingsDcaCallbacks::SETTING_KEYS, 'reference' => &$GLOBALS['TL_LANG']['tl_issue_settings']['setting_key_options'], 
			'eval' => ['mandatory' => true, 'chosen' => true, 'submitOnChange' => true, 'tl_class' => 'w50'], 
			'search' => true, 
			'sql' => "varchar(100) NOT NULL default ''"
			],
		'value_type' => [
			'inputType' => 'select', 
			'options' => ['string', 'bool', 'int', 'json'], 
			'eval' => ['mandatory' => true], 
			'filter' => true, 
			'sql' => "varchar(20) NOT NULL default 'string'"
			],
		'setting_value' => [
			'inputType' => 'text', 
			'eval' => ['tl_class' => 'clr w50'], 
			'load_callback' => [[IssueSettingsDcaCallbacks::class, 'loadValue']], 
			'save_callback' => [[IssueSettingsDcaCallbacks::class, 'saveValue']], 
			'sql' => 'longtext NULL'
			],
		'updated_by' => [
			'sql' => 'int unsigned NULL'
			],
		'updated_at' => [
			'sql' => 'datetime NOT NULL'
			],
		'version' => [
			'sql' => "int unsigned NOT NULL default 1"
			],
	],
];
