<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_service'] = [
	'config' => ['dataContainer' => DC_Table::class, 'enableVersioning' => true, 'sql' => ['keys' => ['id' => 'primary', 'alias' => 'unique']]],
	'list' => ['sorting' => ['mode' => DataContainer::MODE_SORTED, 'fields' => ['title'], 
	'flag' => DataContainer::SORT_ASC], 'label' => ['fields' => ['title', 'alias'], 'format' => '%s [%s]'], 
	'operations' => [
		'edit',
		'children',
		'copy',
		'cut',
		'delete',
		'toggle',
		'show',
		]
	],
	'palettes' => ['default' => '{title_legend},title,alias,description;{defaults_legend},default_priority,default_assignee_id,notification_recipients;{publish_legend},published'],
	'fields' => [
		'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
		'tstamp' => ['sql' => "int unsigned NOT NULL default 0"],
		'title' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 160], 'search' => true, 'sql' => "varchar(160) NOT NULL default ''"],
		'alias' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 160, 'rgxp' => 'alias'], 'search' => true, 'sql' => "varchar(160) NOT NULL default ''"],
		'description' => ['inputType' => 'textarea', 'sql' => 'longtext NULL'],
		'default_priority' => ['inputType' => 'select', 'options' => ['low', 'normal', 'high', 'critical'], 'reference' => &$GLOBALS['TL_LANG']['tl_issue_service']['default_priority_options'], 'sql' => "varchar(16) NOT NULL default 'normal'"],
		'default_assignee_id' => ['inputType' => 'select', 'foreignKey' => 'tl_user.name', 'eval' => ['includeBlankOption' => true, 'chosen' => true], 'relation' => ['type' => 'hasOne', 'load' => 'lazy'], 'sql' => 'int unsigned NULL'],
		'notification_recipients' => ['inputType' => 'textarea', 'sql' => 'longtext NULL'],
		'published' => ['inputType' => 'checkbox', 'toggle' => true, 'filter' => true, 'sql' => "tinyint(1) NOT NULL default 1"],
	],
];
