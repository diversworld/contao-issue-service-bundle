# SLA und Lizenzverwaltung (Version 2.0)

Die Erweiterung setzt die fachlichen SLA- und Lizenzanforderungen aus `Contao_Issue_Service_Management_V2_SLA.docx` im Bundle um. Da das Dokument weder einen Lizenzanbieter/API-Vertrag noch konkrete Vertragszeiten nennt, sind Anbieter und Vertragswerte konfigurierbar. Ein externer Lizenz-/Vertriebsserver wird nicht mit dem Contao-Kundensystem betrieben; die Anbindung und ein separates Signierwerkzeug sind enthalten.

## Installation

1. Abhängigkeiten aktualisieren (`ext-sodium` und `symfony/http-client` werden benötigt).
2. In der Contao-Installation die Migrationen ausführen: `php vendor/bin/contao-console contao:migrate`. `Version200Sla` legt sechs Tabellen, SLA-Ticketfelder und Abfrageindizes additiv an; sie entfernt keine Bestandsdaten. Danach Contao-Cache leeren.
3. Die unten genannten Parameter in der Anwendung konfigurieren. Der öffentliche Schlüssel gehört zur Anwendungskonfiguration, nicht zu bearbeitbaren Lizenzdaten.
4. Als Administrator unter **Lizenzverwaltung** die signierte Lizenz einfügen oder eine Offline-Datei per Command importieren.
5. Unter **SLA-Definitionen** Reaktions-/Lösungszeiten, Zeitzone, Geschäftszeiten und Feiertage eintragen und aktivieren. Bronze, Silber, Gold und Platin werden als Stufen angelegt; es werden keine Vertragszeiten erfunden.
6. Ein SLA dem Service zuordnen. Ticketbezogene Abweichungen können Administratoren und Manager des jeweiligen Services speichern.
7. Unter **SLA-Definitionen → Eskalationen** die drei Stufen konfigurieren: 1 Teamleiter, 2 Service Manager, 3 Administrator. Die Empfänger werden je SLA und Stufe explizit gepflegt, ebenso die Verzögerung ab Fristverletzung. Beispiel: 0, 60 und 240 Kalenderminuten.
8. Contao-Cron regelmäßig durch einen System-Scheduler ausführen. Die Erweiterung registriert einen minütlichen SLA-Job und eine stündliche Online-Lizenzprüfung. Die bestehende Mailer-Konfiguration wird verwendet.

Bis zur Migration bleibt SLA gesperrt; fehlende Lizenzdaten verhindern nicht die grundlegende Ticketbearbeitung. Die Migration wurde nicht automatisch auf der produktiven Datenbank ausgeführt.

## Kalender und Workflow

Geschäftszeiten sind lokale Zeitfenster einer IANA-Zeitzone. Beispiel für Montag bis Freitag mit Mittagspause:

```json
{"1":[["09:00","12:00"],["13:00","17:00"]],"2":[["09:00","17:00"]],"3":[["09:00","17:00"]],"4":[["09:00","17:00"]],"5":[["09:00","17:00"]]}
```

`1` bedeutet Montag, `7` Sonntag. Nicht aufgeführte Tage sind geschlossen. Überlappungen, leere Kalender, ungültige Feiertage und umgekehrte Zeitfenster werden abgewiesen. Für 24/7 jeden Tag mit `[["00:00","24:00"]]` eintragen. Feiertage sind eine Liste wie `["2026-12-25","2026-12-26"]`; sie werden explizit gepflegt, nicht aus einem Bundesland abgeleitet. Sommer-/Winterzeit wird anhand der Zeitzone berücksichtigt. Fristen werden als Unix-Zeitstempel gespeichert. Ein Kalender darf höchstens zehn Jahre Berechnung erfordern; SLA-Dauern sind auf 525600 Geschäftsminuten begrenzt.

Die erstmalige Berechnung startet bei der Ticketanlage. Zuordnung, Antwort und Statuswechsel rufen die Engine direkt auf; der Scheduler verarbeitet auch vorhandene Tickets und später geänderte Service-Zuordnungen. Nur die erste **öffentliche Antwort eines Backend-Bearbeiters** erfüllt das Reaktions-SLA. Interne Notizen und Kundenkommentare erfüllen es nicht. Eine Antwort genau zur Frist gilt als rechtzeitig.

Gelöst/geschlossen hält die Uhren an. Bei Reopen wird die verbleibende Geschäftszeit fortgesetzt, ohne neues vollständiges Zeitbudget. Bereits eingetretene Verletzungen und die erste Antwort bleiben erhalten; eine bereits überfällige Frist wird durch Reopen nicht geheilt. War bei Abschluss noch keine Bearbeiterantwort vorhanden, wird auch deren Restzeit fortgesetzt. Rückfrage-/Wartestatus pausieren nicht automatisch, weil das Dokument hierfür keine Regel festlegt.

Jede Zuordnung speichert den Kalender und die Vertragszeiten als Snapshot am Ticket. Änderungen einer Definition verändern damit keine laufenden Verträge. Ein expliziter Wechsel der SLA-Definition erzeugt eine neue Zuordnung und berechnet ab der ursprünglichen Ticketanlage. Das globale Verletzungskennzeichen und die Historie werden nicht gelöscht. Bereits pausierte Phasen werden bei einer solchen neuen Vertragszuordnung nicht rückwirkend rekonstruiert.

Ohne gültige Lizenz laufen Premium-Berechnungen, Berichte und Eskalationen nicht. Die Basisfunktionen bleiben verfügbar. Nach längerer Deaktivierung kann der Scheduler den aktuellen Ticketstand abgleichen; mehrere vollständig während der Deaktivierung erfolgte Reopen-/Abschlusszyklen werden nicht nachträglich rekonstruiert.

## Eskalationen, Berichte und Sichtbarkeit

Für eine offene überfällige Reaktions- oder Lösungsfrist wird jede konfigurierte Stufe einmal pro Ticket/SLA-Zuordnung eingereiht. Ticket-Zeilensperren und die gemeinsame Transaktion aus Journal und Outbox verhindern doppelte Einreihung bei parallelen Schedulerläufen. Vor Mailversand werden Lizenz und aktuelle Empfänger erneut geprüft. Der bestehende Retry-Mechanismus behandelt Mailfehler. Wie bei einer üblichen SMTP-Outbox kann ein Prozessabbruch nach SMTP-Annahme, aber vor dem Datenbankupdate, eine erneute Zustellung verursachen.

SLA-Dashboard, Definitionen, Eskalationskonfiguration, Rohhistorie und Lizenzverwaltung sind Administratoren vorbehalten. Ticket-Overrides erfordern eine Managerrolle für den Service oder Administratorrechte. Frontend-Anzeigen verwenden die bestehende Ticket-Zugriffsprüfung und zeigen keine internen Empfänger-/Konfigurationsdaten. Die Anzeige enthält Status, Fristen, Geschäftsminuten und den SLA-Verlauf; sie wird beim Seitenaufruf aktualisiert.

Das Dashboard enthält Gesamtzahl, Zahl verletzter Tickets, abgeschlossene Tickets und fristgerecht abgeschlossene Tickets. Die Erfüllungsquote ist `fristgerecht abgeschlossen / abgeschlossen`; ohne abgeschlossene Tickets gibt es keine Quote. Der Verletzungsmonitor zeigt die letzten 200 betroffenen Tickets. CLI-Berichte können anhand des Ticket-Anlagedatums eingegrenzt werden. Berichte verwenden den zuletzt berechneten Stand; gelöschte Tickets und Tickets ohne aktuelle SLA-Zuordnung werden ausgeschlossen. Es handelt sich um Ticketkennzahlen, nicht um eine Quote einzelner Reopen-Zyklen.

## Signierte Lizenzen

In der Anwendungsdatei `config/services.yaml`:

```yaml
parameters:
  contao_issue_service.license_public_key: 'BASE64_ED25519_PUBLIC_KEY'
  contao_issue_service.license_tenant: 'kunde-123'
  contao_issue_service.license_domain: 'support.example.org'
  contao_issue_service.license_endpoint: '' # Vollständiger Validierungs-Endpunkt auf license.diversworld.eu folgt
```

Mandantenkennung und Domain stammen ausschließlich aus vertrauenswürdiger Serverkonfiguration, niemals aus dem HTTP-Host-Header. Die Domain im signierten Payload ist exakt die konfigurierte Domain in Kleinbuchstaben, ohne Protokoll, Pfad oder Port. Ein Bundle in einer Mehrdomaininstallation wird dadurch an die konfigurierte Installation gebunden; ein Hostname pro Request wird nicht als Lizenznachweis verwendet.

Lizenzformat: `base64url(JSON).base64url(Ed25519-Signatur)`, ohne Base64-Padding. Signiert wird exakt der erste, bereits base64url-kodierte Teil. Beispielclaims (Zeitstempel anpassen):

```json
{
  "tenant": "kunde-123",
  "domain": "support.example.org",
  "issued_at": 1790000000,
  "expires_at": 1820000000,
  "mode": "online",
  "refresh_after": 1790086400,
  "status": "valid",
  "features": ["sla"]
}
```

Bei `mode: offline` ist `refresh_after` nicht erforderlich. Eine Offline-Lizenz ist bis `expires_at` gültig. Online-Lizenzen wechseln bei `refresh_after` in eine **30-Tage-Kulanzfrist** und werden spätestens bei `refresh_after + 30 × 86400` ungültig. `expires_at` ist unabhängig davon immer eine harte Grenze. Veränderte Datenbank-Zeitstempel oder ein fehlgeschlagener Serverkontakt verlängern die Kulanz nicht. Ungültige Signaturen, falscher Mandant/Domain, fehlendes SLA-Feature, zukünftige Ausstellung, abgelaufene oder widerrufene Claims sperren Premiumfunktionen.

Jeder Premium-Einstieg prüft die signierte Lizenz serverseitig erneut. Der lokal gespeicherte Status ist kein Freischaltungsnachweis. Offlinefähigkeit bedeutet, dass eine zwischenzeitliche serverseitige Sperre erst beim nächsten erfolgreichen Onlinekontakt bekannt werden kann. Eine vertrauenswürdige Systemuhr ist Voraussetzung.

### Online-Schnittstelle

Als Lizenzserver ist `https://license.diversworld.eu` vorgesehen; er befindet sich im Aufbau. Der genaue Validierungs-Pfad, der API-Vertrag und der öffentliche Ed25519-Prüfschlüssel müssen mit dem Server abgestimmt werden. Bis dahin bleibt `license_endpoint` leer. Die Serverdomain ist nicht mit `license_domain` zu verwechseln: Letzteres bezeichnet die lizenzierte Contao-Installation.

Der Client sendet HTTPS-POST an den konfigurierten Endpoint:

```json
{"token":"SIGNED_TOKEN","tenant":"kunde-123","domain":"support.example.org"}
```

- HTTP 200: `{"token":"NEW_SIGNED_TOKEN"}`. Auch diese Lizenz wird vollständig lokal geprüft.
- HTTP 401, 403 oder 410: ausdrückliche Ablehnung; die gespeicherte Freischaltung wird entfernt.
- Netzwerkfehler, HTTP 5xx oder ungültige Antwort: keine Verlängerung; die vorhandene signierte Lizenz bestimmt Gültigkeit/Kulanz.

TLS-Prüfung bleibt aktiviert; Redirects sind deaktiviert, damit Lizenzdaten nicht an umgeleitete Hosts gehen. Anfragezeit ist begrenzt. Die erste Lizenz muss über die Verwaltung oder Datei eingespielt werden; eine Zahlung/Kundenregistrierung beim Lizenzanbieter gehört nicht zu diesem Bundle. Eine nach längerer Unterbrechung bereits vollständig ungültige Online-Lizenz kann per `--online` erneut geprüft werden; der automatische Job bearbeitet erkannte, noch gültige Online-Lizenzen.

### Lizenzen ausstellen

Der Lizenzanbieter erzeugt und schützt sein Ed25519-Schlüsselpaar außerhalb der Kundeninstallation. **Nur der öffentliche Schlüssel wird auf dem Contao-System eingerichtet.** Das eigenständig ausführbare Hilfsprogramm verwendet eine JSON-Claims-Datei und einen base64-kodierten Ed25519-Secret-Key:

```sh
php tools/sign-license.php claims.json /sicherer/pfad/private-key.base64 > kunde.lic
```

`kunde.lic` enthält nur das signierte Token und kann im Backend eingefügt oder per CLI importiert werden. Das Signierwerkzeug legt keine Schlüssel an und speichert keine Secrets im Repository. Ein vorhandenes Lizenzportal kann dasselbe Format erzeugen; dessen konkrete URL und öffentlicher Schlüssel müssen vom Betreiber bereitgestellt werden.

## Commands

In der Contao-Anwendung:

```sh
php vendor/bin/contao-console issue:license:validate --offline-file=/sicherer/pfad/kunde.lic
php vendor/bin/contao-console issue:license:validate --online
php vendor/bin/contao-console issue:sla:calculate
php vendor/bin/contao-console issue:sla:escalate
php vendor/bin/contao-console issue-service:notifications:dispatch
php vendor/bin/contao-console issue:sla:report --from=2026-01-01 --until=2027-01-01
```

`issue:sla:report` gibt JSON aus. Das Enddatum ist exklusiv. `issue:sla:escalate` berechnet zunächst Fristen und reiht anschließend Eskalationen ein; der Mailversand erfolgt über den vorhandenen Dispatcher. `issue:license:validate` ohne Optionen prüft rein lokal und gibt bei ungültiger Lizenz Exitcode 1 zurück.

## Historie und Sicherheitsgrenzen

SLA-Zuordnungen, Antworten, Abschluss, Wiederaufnahme und einzelne Fristverletzungen werden historisiert. Konfigurationsänderungen werden im selben SLA-Journal mit `issue_id=0` abgelegt. Die Einträge enthalten Akteur, vorherige/nachherige Werte und eine HMAC-SHA256-Kette mit `kernel.secret`. `SlaHistory::verify()` erkennt geänderte Einträge, Kettenbrüche und entfernte mittlere Einträge. DCAs erlauben kein Bearbeiten/Löschen der Historien. Lizenzvalidierungen speichern nur Ergebnis und Zeitpunkt, keine Secrets oder Serverantworten.

Dies ist manipulationsnachweisbare Anwendungshistorie, kein externes WORM-Archiv: Vollständiges Löschen, Abschneiden des Kettenendes, Zugriff auf Datenbank **und** `kernel.secret` oder Manipulation des PHP-Codes werden dadurch nicht verhindert. Für externe Revisionsanforderungen sind regelmäßige unveränderliche Exporte/Backups nötig. Eine Rotation von `kernel.secret` benötigt ein Konzept zum Erhalt des bisherigen Prüfschlüssels.

## Prüfungen

Automatisierte Tests decken Geschäftszeiten, Feiertage, DST, Signaturen, Bindung, Grace-Grenzen, Antworten, Reopen, Overrides, Historienmanipulation, Eskalations-Deduplizierung, Mail-Lizenzsperre, Reports, Commands, Dateiimport, Online-Erneuerung/Ablehnung sowie Backend-Berechtigungen und Twig-Rendering ab. Integrationstests verwenden echtes SQLite; ausschließlich die MySQL-Zeilensperre wird im Testadapter entfernt. Echte Parallelität unter MySQL/MariaDB und Browser-E2E in Contao 5.7 und 6 müssen in einer geeigneten Staging-Umgebung geprüft werden.

Der lokale Test-Autoloader bindet explizit den aktuellen Bundle-Quellcode ein. PHPUnit benötigt DOM/XML/XMLWriter und PDO_SQLite zusätzlich zu den Laufzeitabhängigkeiten.

## Schema-Update: leeres `ENGINE =`

Contao 5.7.13 vergleicht im Migrationscompiler die Engine auch bei Tabellen, die andere Schema-Listener anlegen und die keine Engine im Zielschema deklarieren. Das betrifft in dieser Installation `tl_message_queue`, `tl_news_categories` und `tl_nc_bulky_items`. Dadurch kann Contao ein ungültiges `ALTER TABLE … ENGINE =` erzeugen und zusätzlich die Sekundärindizes löschen, weil es fälschlich einen Engine-Wechsel annimmt.

Das Bundle normalisiert deshalb über einen Decorator des Contao-Schemaproviders fehlende/leere Engine-Angaben nach Abschluss aller Schema-Listener. Vorhandene Tabellen behalten ihre tatsächliche Engine; neue Tabellen verwenden die konfigurierte Standardengine bzw. InnoDB. Explizite Engine-Angaben, Spalten und Indizes bleiben unverändert. Es werden keine Vendor-Dateien gepatcht. Die SLA-Migration verwendet außerdem dieselben Indexnamen wie Contao (`sla_state_response_due_at`, `sla_id_stage` usw.).

Nach Installation der Korrektur zuerst den Cache leeren und den Dry-Run prüfen:

```sh
php vendor/bin/contao-console cache:clear
php vendor/bin/contao-console contao:migrate --dry-run
```

Falls ein vorheriger Lauf die Indizes bereits gelöscht hat, erscheinen anschließend deren `CREATE INDEX`-/`CREATE UNIQUE INDEX`-Anweisungen. Ein regulärer Migrationslauf stellt diese wieder her. Ein leeres `ENGINE =` darf nicht mehr vorkommen. `--with-deletes` ist für diese Reparatur nicht erforderlich.
