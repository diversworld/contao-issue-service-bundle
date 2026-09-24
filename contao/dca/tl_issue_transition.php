<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_transition'] = [
	'config' => ['dataContainer' => DC_Table::class, 'enableVersioning' => true, 'sql' => ['keys' => ['id' => 'primary', 'from_status_id,to_status_id,role_key' => 'unique']]],
	'list' => ['sorting' => ['mode' => DataContainer::MODE_SORTED, 'fields' => ['from_status_id', 'to_status_id'], 'flag' => DataContainer::SORT_ASC], 'label' => ['fields' => ['from_status_id', 'to_status_id', 'role_key'], 'format' => '%s -> %s [%s]'], 'operations' => ['edit' => ['href' => 'act=edit', 'icon' => 'edit.svg'], 'delete' => ['href' => 'act=delete', 'icon' => 'delete.svg'], 'show' => ['href' => 'act=show', 'icon' => 'show.svg']]],
	'palettes' => ['default' => '{transition_legend},from_status_id,to_status_id,role_key,require_public_comment,published'],
	'fields' => [
		'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
		'tstamp' => ['sql' => "int unsigned NOT NULL default 0"],
		'from_status_id' => ['inputType' => 'select', 'foreignKey' => 'tl_issue_status.title', 'eval' => ['mandatory' => true, 'chosen' => true], 'relation' => ['type' => 'hasOne', 'load' => 'eager'], 'sql' => "int unsigned NOT NULL default 0"],
		'to_status_id' => ['inputType' => 'select', 'foreignKey' => 'tl_issue_status.title', 'eval' => ['mandatory' => true, 'chosen' => true], 'relation' => ['type' => 'hasOne', 'load' => 'eager'], 'sql' => "int unsigned NOT NULL default 0"],
		'role_key' => ['inputType' => 'select', 'options' => ['member', 'agent', 'manager'], 'eval' => ['mandatory' => true], 'sql' => "varchar(40) NOT NULL default ''"],
		'require_public_comment' => ['inputType' => 'checkbox', 'sql' => "tinyint(1) NOT NULL default 0"],
		'published' => ['inputType' => 'checkbox', 'toggle' => true, 'filter' => true, 'sql' => "tinyint(1) NOT NULL default 1"],
	],
];
