<?php

declare(strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla_priority'] = [
    'config' => ['dataContainer' => \Contao\DC_Table::class, 'enableVersioning' => true, 'notDeletable' => true, 'sql' => ['keys' => ['id' => 'primary', 'sla_id,priority' => 'unique']]],
    'list' => ['sorting' => ['mode' => 1, 'fields' => ['id'], 'panelLayout' => 'filter;search,limit'], 'label' => ['fields' => ['priority'], 'format' => '%s'], 'operations' => ['edit', 'show']],
    'palettes' => ['default' => '{title_legend},sla_id,priority,response_minutes,resolve_minutes,published'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'sla_id' => ['label' => ['SLA', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'select', 'foreignKey' => 'tl_issue_sla.title', 'eval' => ['includeBlankOption' => true, 'chosen' => true]],
        'priority' => ['label' => ['Priorität', ''], 'sql' => "varchar(16) NOT NULL default 'normal'", 'inputType' => 'select', 'options' => ['critical', 'high', 'normal', 'low']],
        'response_minutes' => ['label' => ['Reaktionszeit (Supportminuten)', ''], 'sql' => "int unsigned NOT NULL default 60", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'resolve_minutes' => ['label' => ['Lösungszeit (Supportminuten)', ''], 'sql' => "int unsigned NOT NULL default 480", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'published' => ['label' => ['Aktiv', ''], 'sql' => "tinyint(1) NOT NULL default 0", 'inputType' => 'checkbox'],
    ],
];
