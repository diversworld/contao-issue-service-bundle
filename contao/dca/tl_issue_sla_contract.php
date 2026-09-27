<?php

declare(strict_types=1);
$GLOBALS['TL_DCA']['tl_issue_sla_contract'] = [
    'config' => ['dataContainer' => \Contao\DC_Table::class, 'enableVersioning' => true, 'notDeletable' => true, 'sql' => ['keys' => ['id' => 'primary', 'member_id,service_id' => 'index']]],
    'list' => ['sorting' => ['mode' => 1, 'fields' => ['id'], 'panelLayout' => 'filter;search,limit'], 'label' => ['fields' => ['contract_number'], 'format' => '%s'], 'operations' => ['edit', 'show']],
    'palettes' => ['default' => '{title_legend},contract_number,member_id,service_id,sla_id,calendar_id,starts_at,ends_at,term_months,notice_days,scope,published'],
    'fields' => [
        'id' => ['sql' => 'int unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => 'int unsigned NOT NULL default 0'],
        'contract_number' => ['label' => ['Vertragsnummer', ''], 'sql' => "varchar(160) NOT NULL default ''", 'inputType' => 'text', 'eval' => ['maxlength' => 160]],
        'member_id' => ['label' => ['Kunde', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'select', 'foreignKey' => 'tl_member.email', 'eval' => ['includeBlankOption' => true, 'chosen' => true]],
        'service_id' => ['label' => ['Service (leer = alle)', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'select', 'foreignKey' => 'tl_issue_service.title', 'eval' => ['includeBlankOption' => true, 'chosen' => true]],
        'sla_id' => ['label' => ['SLA', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'select', 'foreignKey' => 'tl_issue_sla.title', 'eval' => ['includeBlankOption' => true, 'chosen' => true]],
        'calendar_id' => ['label' => ['Kundenkalender (leer = SLA-Kalender)', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'select', 'foreignKey' => 'tl_issue_sla_calendar.title', 'eval' => ['includeBlankOption' => true, 'chosen' => true]],
        'starts_at' => ['label' => ['Vertragsbeginn', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'date', 'datepicker' => true]],
        'ends_at' => ['label' => ['Vertragsende (ausschließlich; leer = unbefristet)', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'date', 'datepicker' => true]],
        'term_months' => ['label' => ['Vereinbarte Laufzeit (Monate)', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'notice_days' => ['label' => ['Kündigungsfrist (Kalendertage)', ''], 'sql' => "int unsigned NOT NULL default 0", 'inputType' => 'text', 'eval' => ['rgxp' => 'digit', 'minval' => 0]],
        'scope' => ['label' => ['Leistungsumfang', ''], 'sql' => "text NULL", 'inputType' => 'textarea', 'default' => ''],
        'published' => ['label' => ['Aktiv', ''], 'sql' => "tinyint(1) NOT NULL default 0", 'inputType' => 'checkbox'],
    ],
];

$GLOBALS['TL_DCA']['tl_issue_sla_contract']['fields']['contract_number']['eval']['mandatory'] = true;

$GLOBALS['TL_DCA']['tl_issue_sla_contract']['fields']['member_id']['eval']['mandatory'] = true;

$GLOBALS['TL_DCA']['tl_issue_sla_contract']['fields']['sla_id']['eval']['mandatory'] = true;

$GLOBALS['TL_DCA']['tl_issue_sla_contract']['fields']['starts_at']['eval']['mandatory'] = true;

$GLOBALS['TL_DCA']['tl_issue_sla_contract']['fields']['contract_number']['search'] = true;
$GLOBALS['TL_DCA']['tl_issue_sla_contract']['fields']['member_id']['filter'] = true;
