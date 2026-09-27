<?php

declare(strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla_calendar'] = [
    'config' => ['dataContainer' => \Contao\DC_Table::class, 'enableVersioning' => true, 'notDeletable' => true, 'sql' => ['keys' => ['id' => 'primary']]],
    'list' => ['sorting' => ['mode' => 1, 'fields' => ['id'], 'panelLayout' => 'filter;search,limit'], 'label' => ['fields' => ['title'], 'format' => '%s'], 'operations' => ['edit', 'show']],
    'palettes' => ['default' => '{title_legend},title,country,region,timezone,always_open,business_hours,holidays,maintenance,published'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'title' => ['label' => ['Kalender', ''], 'sql' => "varchar(160) NOT NULL default ''", 'inputType' => 'text', 'eval' => ['maxlength' => 160]],
        'country' => ['label' => ['Land (ISO-Code, z. B. DE)', ''], 'sql' => "varchar(2) NOT NULL default ''", 'inputType' => 'text', 'eval' => ['maxlength' => 2]],
        'region' => ['label' => ['Bundesland / Region (z. B. BY)', ''], 'sql' => "varchar(32) NOT NULL default ''", 'inputType' => 'text', 'eval' => ['maxlength' => 32]],
        'timezone' => ['label' => ['Zeitzone', ''], 'sql' => "varchar(64) NOT NULL default ''", 'inputType' => 'text', 'eval' => ['maxlength' => 64]],
        'always_open' => ['label' => ['24/7-Support', ''], 'sql' => "tinyint(1) NOT NULL default 0", 'inputType' => 'checkbox'],
        'business_hours' => ['label' => ['Geschäftszeiten', ''], 'sql' => "text NULL", 'inputType' => 'textarea', 'default' => '{"1":[["09:00","18:00"]],"2":[["09:00","18:00"]],"3":[["09:00","18:00"]],"4":[["09:00","18:00"]],"5":[["09:00","18:00"]]}'],
        'holidays' => ['label' => ['Feiertage (JSON: YYYY-MM-DD)', ''], 'sql' => "text NULL", 'inputType' => 'textarea', 'default' => '[]'],
        'maintenance' => ['label' => ['Wartungsfenster (JSON: Paare aus ISO-8601 mit Zeitzone)', ''], 'sql' => "text NULL", 'inputType' => 'textarea', 'default' => '[]'],
        'published' => ['label' => ['Aktiv', ''], 'sql' => "tinyint(1) NOT NULL default 0", 'inputType' => 'checkbox'],
    ],
];

$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['timezone']['default'] = 'Europe/Berlin';
$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['business_hours'] = [
    'inputType' => 'rowWizard',
    'fields' => [
        'day' => ['label' => ['Wochentag', ''], 'inputType' => 'select', 'options' => [1,2,3,4,5,6,7], 'reference' => [1=>'Montag',2=>'Dienstag',3=>'Mittwoch',4=>'Donnerstag',5=>'Freitag',6=>'Samstag',7=>'Sonntag'], 'eval' => ['mandatory' => true]],
        'from' => ['label' => ['Beginn (HH:MM)', ''], 'inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 5]],
        'to' => ['label' => ['Ende (HH:MM)', ''], 'inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 5]],
    ],
    'default' => '{"1":[["09:00","18:00"]],"2":[["09:00","18:00"]],"3":[["09:00","18:00"]],"4":[["09:00","18:00"]],"5":[["09:00","18:00"]]}',
    'eval' => ['sortable' => false], 'sql' => 'text NULL',
];

$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['holidays'] = [
    'label' => ['Feiertage', 'Datumswerte des gewählten Landes/Bundeslandes; jährlich ergänzen.'],
    'inputType' => 'rowWizard', 'fields' => ['date' => ['label' => ['Datum (YYYY-MM-DD)', ''], 'inputType' => 'text', 'eval' => ['mandatory' => true]]],
    'eval' => ['sortable' => false], 'sql' => 'text NULL',
];
$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['maintenance'] = [
    'label' => ['Wartungsfenster', 'Beginn und Ende mit Zeitzone, z. B. 2026-10-01T09:00:00+02:00.'],
    'inputType' => 'rowWizard', 'fields' => [
        'from' => ['label' => ['Beginn', ''], 'inputType' => 'text', 'eval' => ['mandatory' => true]],
        'to' => ['label' => ['Ende', ''], 'inputType' => 'text', 'eval' => ['mandatory' => true]],
    ], 'eval' => ['sortable' => false], 'sql' => 'text NULL',
];
$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['business_hours']['label'] = ['Supportzeiten', 'Bei 24/7 werden alle Wochentage rund um die Uhr berücksichtigt.'];
$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['title']['eval']['mandatory'] = true;
$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['timezone']['eval']['mandatory'] = true;

$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['business_hours']['eval']['mandatory'] = false;
$GLOBALS['TL_DCA']['tl_issue_sla_calendar']['fields']['business_hours']['eval']['min'] = 0;
