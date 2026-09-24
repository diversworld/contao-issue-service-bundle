<?php

$GLOBALS['TL_LANG']['tl_issue_settings']['setting_legend'] = 'Einstellung';
$GLOBALS['TL_LANG']['tl_issue_settings']['new'] = ['Neue Einstellung', 'Eine neue Einstellung anlegen.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['edit'] = ['Einstellung bearbeiten', 'Einstellung ID %s bearbeiten.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['delete'] = ['Einstellung loeschen', 'Einstellung ID %s loeschen.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['show'] = ['Einstellung-Details', 'Details der Einstellung ID %s anzeigen.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_key'] = ['Einstellung', 'Wählen Sie die zu bearbeitende Einstellung.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value'] = ['Wert', 'Legen Sie den Wert fest.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['value_type'] = ['Werttyp', 'Interner Typ der Einstellung.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_require_login'] = ['Login erforderlich', 'Nur angemeldete Mitglieder dürfen Issues erstellen.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_ticket_pattern'] = ['Ticketnummer-Muster', 'Verfügbare Platzhalter: {SERVICE}, {YEAR}, {SEQ}.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_allowed_extensions'] = ['Erlaubte Dateitypen', 'Wählen Sie die erlaubten Dateiendungen für Anhänge.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_max_file_size'] = ['Maximale Dateigröße', 'Maximale Dateigröße in Bytes.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_max_files_per_issue'] = ['Maximale Anhänge pro Issue', 'Maximale Anzahl von Anhängen pro Issue.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_retention_policy'] = ['Aufbewahrung geschlossener Issues', 'Anzahl der Tage, nach denen geschlossene Issues in der Bereinigung berücksichtigt werden.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_reopen_roles'] = ['Rollen zum Wiederöffnen', 'Wählen Sie, welche Rollen ein Issue wieder öffnen dürfen.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_mail_recipients'] = ['Benachrichtigungsempfänger', 'Geben Sie eine E-Mail-Adresse pro Zeile ein.'];
$GLOBALS['TL_LANG']['tl_issue_settings']['setting_value_service_scoped_permissions'] = ['Servicebezogene Berechtigungen', 'Berechtigungen auf zugeordnete Services einschränken.'];

$GLOBALS['TL_LANG']['tl_issue_settings']['setting_key_options'] = [
    'require_login' => ['Login erforderlich'],
    'ticket_pattern' => ['Ticketnummer-Muster'],
    'allowed_extensions' => ['Erlaubte Dateitypen'],
    'max_file_size' => ['Maximale Dateigröße'],
    'max_files_per_issue' => ['Maximale Anhänge pro Issue'],
    'retention_policy' => ['Aufbewahrung geschlossener Issues'],
    'reopen_roles' => ['Rollen zum Wiederöffnen'],
    'mail_recipients' => ['Benachrichtigungsempfänger'],
    'service_scoped_permissions' => ['Servicebezogene Berechtigungen'],
];

$GLOBALS['TL_LANG']['tl_issue_settings']['role_options'] = [
    'member' => 'Mitglied',
    'agent' => 'Bearbeiter',
    'manager' => 'Manager',
];