<?php

$GLOBALS['TL_LANG']['tl_issue_transition']['transition_legend'] = 'Übergang';

$GLOBALS['TL_LANG']['tl_issue_transition']['new'] = ['Neuer Übergang', 'Eine neue Workflow-Regel anlegen.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['edit'] = ['Übergang bearbeiten', 'Übergang ID %s bearbeiten.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['delete'] = ['Übergang löschen', 'Übergang ID %s löschen.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['show'] = ['Übergang-Details', 'Details des Übergangs ID %s anzeigen.'];

$GLOBALS['TL_LANG']['tl_issue_transition']['from_status_id'] = ['Von Status', 'Ausgangsstatus des Übergangs.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['to_status_id'] = ['Nach Status', 'Zielstatus des Übergangs.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['role_key'] = ['Rolle', 'Rolle, die diesen Übergang ausführen darf.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['require_public_comment'] = ['Öffentlichen Kommentar verlangen', 'Beim Übergang muss ein öffentlicher Kommentar erfasst werden.'];
$GLOBALS['TL_LANG']['tl_issue_transition']['published'] = ['Aktiviert', 'Den Übergang im Workflow verfügbar machen.'];

$GLOBALS['TL_LANG']['tl_issue_transition']['role_key_options'] = [
    'member' => 'Mitglied',
    'agent' => 'Bearbeiter',
    'manager' => 'Manager',
];
$GLOBALS['TL_LANG']['tl_issue_transition']['statuses'] = ['Status verwalten', 'Die verfügbaren Ticketstatus bearbeiten.'];

$GLOBALS['TL_LANG']['tl_issue_transition']['conditions_legend'] = 'Bedingungen';

$GLOBALS['TL_LANG']['tl_issue_transition']['publish_legend'] = 'Aktivierung';

$GLOBALS['TL_LANG']['tl_issue_transition']['sameStatus'] = 'Ausgangsstatus und Zielstatus müssen unterschiedlich sein.';

$GLOBALS['TL_LANG']['tl_issue_transition']['duplicateRule'] = 'Für diesen Ausgangsstatus, Zielstatus und diese Rolle besteht bereits eine Regel.';
