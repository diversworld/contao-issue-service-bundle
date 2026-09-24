<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_settings'] = [
	'config' => ['dataContainer' => DC_Table::class, 'enableVersioning' => true, 'sql' => ['keys' => ['id' => 'primary', 'setting_key' => 'unique']]],
	'list' => ['sorting' => ['mode' => DataContainer::MODE_SORTED, 'fields' => ['setting_key'], 'flag' => DataContainer::SORT_ASC], 'label' => ['fields' => ['setting_key', 'value_type'], 'format' => '%s [%s]'], 'operations' => ['edit' => ['href' => 'act=edit', 'icon' => 'edit.svg'], 'delete' => ['href' => 'act=delete', 'icon' => 'delete.svg'], 'show' => ['href' => 'act=show', 'icon' => 'show.svg']]],
	'palettes' => ['default' => '{setting_legend},setting_key,value_type,setting_value'],
	'fields' => [
		'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
		'tstamp' => ['sql' => "int unsigned NOT NULL default 0"],
		'setting_key' => ['inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 100], 'search' => true, 'sql' => "varchar(100) NOT NULL default ''"],
		'value_type' => ['inputType' => 'select', 'options' => ['string', 'bool', 'int', 'json'], 'eval' => ['mandatory' => true], 'filter' => true, 'sql' => "varchar(20) NOT NULL default 'string'"],
		'setting_value' => ['inputType' => 'textarea', 'sql' => 'longtext NULL'],
		'updated_by' => ['sql' => 'int unsigned NULL'],
		'updated_at' => ['sql' => 'datetime NOT NULL'],
		'version' => ['sql' => "int unsigned NOT NULL default 1"],
	],
];
