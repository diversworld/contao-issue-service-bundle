<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_status'] = [
	'config' => ['dataContainer' => DC_Table::class, 'enableVersioning' => true, 'sql' => ['keys' => ['id' => 'primary', 'status_key' => 'unique']]],
	'list' => ['sorting' => ['mode' => DataContainer::MODE_SORTED, 
	'fields' => ['sort_order'], 'flag' => DataContainer::SORT_ASC], 
	'label' => ['fields' => ['title', 'status_key'], 'format' => '%s [%s]'], 
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
	'palettes' => ['default' => '{title_legend},status_key,title,sort_order,color;{state_legend},is_initial,is_resolved,is_closed,published'],
	'fields' => [
		'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
		'tstamp' => ['sql' => "int unsigned NOT NULL default 0"],
		'status_key' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 32, 'rgxp' => 'alias'], 'search' => true, 'sql' => "varchar(32) NOT NULL default ''"],
		'title' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 100], 'search' => true, 'sql' => "varchar(100) NOT NULL default ''"],
		'sort_order' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'rgxp' => 'natural'], 'sql' => "smallint unsigned NOT NULL default 0"],
		'is_initial' => ['inputType' => 'checkbox', 'sql' => "tinyint(1) NOT NULL default 0"],
		'is_resolved' => ['inputType' => 'checkbox', 'sql' => "tinyint(1) NOT NULL default 0"],
		'is_closed' => ['inputType' => 'checkbox', 'sql' => "tinyint(1) NOT NULL default 0"],
		'color' => ['inputType' => 'text', 'eval' => ['maxlength' => 6, 'colorpicker' => true, 'isHexColor' => true, 'decodeEntities' => true, 'tl_class' => 'w25 wizard'], 'sql' => 'varchar(6) NULL'],
		'published' => ['inputType' => 'checkbox', 'toggle' => true, 'filter' => true, 'sql' => "tinyint(1) NOT NULL default 1"],
	],
];
