<?php

$GLOBALS['TL_LANG']['tl_issue_profile']['title'] = ['Profilname', 'Name der Aufgabe oder des Mandanten.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['description'] = ['Beschreibung', 'Verwendungszweck dieses Profils.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['ticket_pattern'] = ['Ticketnummer-Muster', 'Platzhalter: {SERVICE}, {YEAR}, {SEQ}. Das Muster muss {SEQ} enthalten.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['require_login'] = ['Login erforderlich', 'Bisheriger Parameter. Das Frontend unterstützt derzeit ausschließlich angemeldete Mitglieder.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['allowed_extensions'] = ['Erlaubte Dateitypen', 'Für dieses Profil erlaubte Anhänge.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['max_file_size'] = ['Maximale Dateigröße', 'Größe in Bytes, z. B. 10485760 für 10 MiB.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['max_files_per_issue'] = ['Maximale Anhänge pro Ticket', 'Gesamtzahl einschließlich später ergänzter Anhänge.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['attachment_storage'] = ['Speicherort', 'var: nur berechtigte Downloads. files: öffentliche Ordner erlauben Direktzugriffe ohne Ticketberechtigung.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['attachment_directory'] = ['Unterverzeichnis in var', 'Nur bei Speicherort var: optionales Unterverzeichnis unter var/issue-attachments.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['attachment_folder'] = ['Ordner unter files', 'Nur bei Speicherort files erforderlich. Öffentliche Ordner erlauben direkten Zugriff. Änderungen betreffen neue Tickets.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['mail_recipients'] = ['Benachrichtigungsempfänger', 'Eine E-Mail-Adresse pro Zeile.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['retention_days'] = ['Aufbewahrung in Tagen', 'Geschlossene Tickets nach dieser Frist für die Bereinigung berücksichtigen.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['reopen_roles'] = ['Rollen zum Wiederöffnen', 'Zusätzliche Freigabe für Wechsel von gelösten oder geschlossenen Tickets zurück in einen offenen Status. Eine aktive Workflow-Regel ist ebenfalls erforderlich.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['service_scoped_permissions'] = ['Servicebezogene Berechtigungen', 'Workflow-Rollen aus Benutzergruppen nur für deren zugeordnete Services verwenden. Dies ersetzt keine vollständige Mandantentrennung.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['title_legend'] = 'Konfigurationsprofil';
$GLOBALS['TL_LANG']['tl_issue_profile']['ticket_legend'] = 'Tickets';
$GLOBALS['TL_LANG']['tl_issue_profile']['upload_legend'] = 'Anhänge und Ablage';
$GLOBALS['TL_LANG']['tl_issue_profile']['notification_legend'] = 'Benachrichtigungen';
$GLOBALS['TL_LANG']['tl_issue_profile']['policy_legend'] = 'Weitere Parameter';
$GLOBALS['TL_LANG']['tl_issue_profile']['new'] = ['Neues Profil', 'Eine vollständige Konfiguration anlegen.'];

$GLOBALS['TL_LANG']['tl_issue_profile']['attachment_storage_options'] = ['var' => 'Private Ablage in var', 'files' => 'Ordner unter files'];

$GLOBALS['TL_LANG']['tl_issue_profile']['issue_notifications_legend'] = 'Ticketbenachrichtigungen';
$GLOBALS['TL_LANG']['tl_issue_profile']['issue_notify_issue_created'] = ['Ticketerstellung', 'E-Mail-Benachrichtigungen für dieses Ereignis aktivieren.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['issue_notify_assignment_changed'] = ['Bearbeiterzuweisung', 'E-Mail-Benachrichtigungen für dieses Ereignis aktivieren.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['issue_notify_status_changed'] = ['Statusänderung', 'E-Mail-Benachrichtigungen für dieses Ereignis aktivieren.'];
$GLOBALS['TL_LANG']['tl_issue_profile']['issue_notify_public_comment_added'] = ['Öffentliche Antwort', 'E-Mail-Benachrichtigungen für dieses Ereignis aktivieren.'];
