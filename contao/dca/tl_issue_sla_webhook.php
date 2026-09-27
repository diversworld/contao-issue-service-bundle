<?php

declare(strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla_webhook'] = [
    'config' => ['dataContainer' => \Contao\DC_Table::class, 'enableVersioning' => true, 'notDeletable' => true, 'sql' => ['keys' => ['id' => 'primary', 'issue_id,event_key' => 'unique']]],
    'list' => ['sorting' => ['mode' => 1, 'fields' => ['id'], 'panelLayout' => 'filter;search,limit'], 'label' => ['fields' => ['event_key'], 'format' => '%s'], 'operations' => ['edit', 'show']],
    'palettes' => ['default' => '{title_legend},event_key,status,attempts,next_attempt_at,sent_at,last_error'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'issue_id' => ['label' => ['Ticket', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'rule_id' => ['label' => ['Regel', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'event_key' => ['label' => ['Ereignis', ''], 'sql' => "varchar(96) NOT NULL default ''", 'inputType' => 'text', 'eval' => ['maxlength' => 96]],
        'payload' => ['label' => ['Nutzdaten', ''], 'sql' => "text NULL", 'inputType' => 'textarea', 'default' => ''],
        'status' => ['label' => ['Status', ''], 'sql' => "varchar(16) NOT NULL default ''", 'inputType' => 'text', 'eval' => ['maxlength' => 16]],
        'attempts' => ['label' => ['Versuche', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'next_attempt_at' => ['label' => ['Nächster Versuch', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'sent_at' => ['label' => ['Gesendet', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'last_error' => ['label' => ['Letzter Fehler', ''], 'sql' => "text NULL", 'inputType' => 'textarea', 'default' => ''],
    ],
];

$GLOBALS['TL_DCA']['tl_issue_sla_webhook']['config']['closed'] = true;
$GLOBALS['TL_DCA']['tl_issue_sla_webhook']['config']['notEditable'] = true;
$GLOBALS['TL_DCA']['tl_issue_sla_webhook']['list']['operations'] = ['show'];
