# Betrieb, Migrationen und Fehlerbehebung

[Wiki-Start](Home.md)

## Aktualisieren und sichern

Datenbank sowie die verwendete Ablage in `var` und/oder `files` zusammen sichern. Nach einem Paketupdate `contao:migrate` ausführen und den Cache erneuern. Die Migrationen ändern nur ihren jeweiligen Bereich; vorhandene Einstellungen werden dort erhalten, wo die Migration fehlende Felder oder Datensätze ergänzt.

| Migration | Zweck |
| --- | --- |
| `Version100Schema` | Grundtabellen und Standardstatus |
| `Version101DefaultCategories`, `Version102TicketCategories` | Kategorie-Ergänzungen |
| `Version110Profiles` | Benannte Konfigurationsprofile |
| `Version120WorkflowGroups` | Rollen und Services an Benutzergruppen |
| `Version121WorkflowDrafts` | Kopierbare Workflowentwürfe ohne Zielstatus |
| `Version122ExampleWorkflow` | 23 fehlende Beispielregeln ergänzen |
| `Version123NotificationPreferences` | Benachrichtigungsschalter an Profilen, Benutzern und Mitgliedern |

Gelöschte Beispielregeln werden beim nächsten passenden Migrationslauf wieder ergänzt. Regeln deshalb deaktivieren, wenn sie dauerhaft nicht verwendet werden sollen.

## E-Mail-Warteschlange

Automatischer Versand erfolgt nach Anfragen mit neuen Nachrichten und über einen minütlichen Contao-Cronjob. Bei besuchergesteuertem Cron können geringe Zugriffszahlen den Versand verzögern. Für regelmäßigen Betrieb den Contao-Cron serverseitig planen.

Manuelle Verarbeitung fälliger Nachrichten, mit tatsächlichem Mailversand:

```bash
php vendor/bin/contao-console issue-service:notifications:dispatch
```

In DDEV `ddev exec` voranstellen. Für lokale Mails lässt sich die übliche DDEV-Mailpit-Oberfläche mit `ddev mailpit` öffnen. [Benachrichtigungen](Benachrichtigungen.md) beschreibt Empfänger, Wiederholungen und persönliche Einstellungen.

## Aufbewahrung

Vorschau ohne Änderung:

```bash
php vendor/bin/contao-console issue-service:retention
```

Anwendung der Änderungen nach Prüfung der Vorschau:

```bash
php vendor/bin/contao-console issue-service:retention --execute
```

Berücksichtigt werden geschlossene Tickets nach der Frist ihres Profils. Der aktuelle Befehl entfernt die Mitgliedszuordnung, anonymisiert Titel/Beschreibung, leert Lösung und Gastzugriffswert und setzt `deleted_at`. Er löscht weder Anhangsdateien noch alle personenbezogenen Inhalte in Kommentaren, Historie oder Maildaten. Er ist daher keine vollständige Datenlöschung oder vollständige Anonymisierung. Die Ausführung muss betrieblich geplant werden; ein Retention-Cronjob ist nicht enthalten.

## Fehler eingrenzen

| Symptom | Prüfen |
| --- | --- |
| Keine Services/Kategorien im Frontend | Veröffentlichung und Kategoriezuordnung zum Service |
| Falsche Detailseite | `jumpTo` der Liste/Erstellung und Einbindung des Detailmoduls |
| Kein erlaubter Statuswechsel | Aktive Regel, veröffentlichter Zielstatus, Gruppenrolle, Servicefreigabe, Wiederöffnungsrolle |
| Pflichtantwort fehlt | Öffentliche Journalantwort verwenden; interne Notiz reicht nicht |
| Neue Regel nach Kopieren unvollständig | Zielstatus wählen und Regel aktivieren; Kopie beginnt als Entwurf |
| Keine Benachrichtigung | Profilschalter, persönliche Auswahl, gültige Adressen, Mailtransport und Administratoradresse |
| Mail wartet | `tl_issue_notification`: `status`, `last_error`, `attempt_count`, `next_attempt_at`; Cron prüfen |
| Mailstatus `skipped` | Empfänger oder Ereignis vor Versand nicht mehr freigegeben |
| Upload abgelehnt | Endung/Inhalt, Dateigröße, Gesamtzahl; zusätzlich PHP-Uploadgrenzen |
| Datei nicht erreichbar | Gespeicherter Ablagepfad, Ordner, Schreib-/Leserechte und Zugriffsberechtigung |
| Profilfelder fehlen | Migration, Cache und beim Frontendprofil die Auswahl bearbeitbarer Felder |

Anwendungsfehler stehen in den Contao-/Symfony-Projektlogs, lokal typischerweise unter `var/logs`.
