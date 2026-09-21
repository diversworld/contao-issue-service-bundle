# Verbleibende Integrations- und Anpassungsschritte

## Paket und Versionsmatrix

- `vendor` und Namespace durch die tatsächliche Organisation ersetzen.
- Lizenz, Repository, Supportinformationen und Releaseprozess ergänzen.
- Composer-Auflösung je Zielprojekt für Contao 5.7 und Contao 6 inklusive `--prefer-lowest` prüfen.
- PHP-Untergrenze an die tatsächlich freigegebenen Contao-Versionen anpassen.
- Symfony-Einzelabhängigkeiten nach Composer-Auflösung reduzieren oder präzisieren.

## Persistenz und Migration

- Vollständige produktive Migration aus dem Pflichtenheft in einzelne, versionierte Migrationsschritte aufteilen.
- Fremdschlüsselstrategie und Löschverhalten im Ziel-DBMS freigeben.
- Seed-Migration für Status und Übergänge idempotent implementieren.
- Upgrade-Fixtures aus einem realistischen Contao-5.7-Datenbestand erstellen.
- Datenbanktests mit tatsächlich eingesetzter MariaDB-/MySQL-Version ausführen.

## Backend

- Alle DCA-Felder, Labels, Relationen, `options_callback`, Filter und Suchfelder vervollständigen.
- Backend-Menü über den dokumentierten Menu-Event-Listener statt ausschließlich über Legacy-`BE_MOD` integrieren.
- DCA-Aktionsrechte mit Contao Data-Container-Permissions und Service-Scope-Voter verbinden.
- Eigene Backend-Aktionen für öffentliche Antwort, interne Notiz, Statuswechsel, Verlauf und Anhänge implementieren.
- SetupValidator und Administrationsoberfläche mit feldspezifischen Fehlermeldungen fertigstellen.
- Gruppenpflege für `tl_issue_service_group` umsetzen.

## Frontend

- Frontend-Seiteneinbindung festlegen: echte Seitenroute, Frontend-Modul oder Content-Element.
- Routen mit `_scope=frontend` und Contao-Seitenkontext im Zielprojekt validieren.
- Dynamische Kategorieauswahl mit serverseitiger Validierung implementieren.
- Gastmodus einschließlich Token-Erzeugung, Hashspeicherung, Rotation, Widerruf und Rate Limiting fertigstellen.
- Wiederöffnungscontroller und Workflow-Prüfung ergänzen.
- Pagination mit Gesamtzahl und barrierearmen Navigationselementen ergänzen.
- Twig-Templates in das konkrete Theme integrieren und übersetzen.

## Sicherheit und Dateien

- Malware-Scanner-Adapter auswählen und `scan_status` vor Download erzwingen.
- MIME- und Dateisignaturprüfung ergänzen; Endung allein nicht akzeptieren.
- Dateispeicher, Berechtigungen, Backup, Wiederherstellung und Quarantänepfad festlegen.
- Security-Header und Content-Disposition gegen problematische Dateinamen testen.
- Rate Limiter für Gastmodus und Kommentare konfigurieren.
- Datenschutz- und Informationssicherheitsreview durchführen.

## Benachrichtigungen und Betrieb

- Mailtemplates je Ereignis und Sprache erstellen.
- Empfängerlogik für Melder, Agent, Service-Mailbox und Zuweisung finalisieren.
- Scheduler/Cron für Notification-Retry und Retention einrichten.
- Monitoring und Alarmierung für fehlgeschlagene Jobs, Queue, Uploads und 403-Häufungen einrichten.
- Retention auf Anhänge, Kommentare, Historie und Notification-Logs erweitern.
- Backup-/Restore-Test einschließlich Attachment-Storage durchführen.

## Qualität und Freigabe

- PHP-Lint, PHPUnit, PHPStan und Composer Audit in CI ausführen.
- Functional- und E2E-Tests in echten Contao-5.7- und Contao-6-Installationen ausführen.
- Deprecation-Logs in Contao 5.7 auswerten und als Release-Gate definieren.
- Barrierefreiheit, Übersetzungen sowie Fehler- und Leerzustände prüfen.
- Administrator-, Installations-, Betriebs- und Abnahmehandbuch finalisieren.
- Paket erst nach erfolgreicher CI-Matrix, Security Review und Abnahme kennzeichnen.
