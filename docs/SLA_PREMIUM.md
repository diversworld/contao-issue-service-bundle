# SLA-Service-Management

Alle hier beschriebenen Funktionen gehören zum Lizenzmerkmal `sla`. Backend-Verwaltung,
Dashboard und Downloads benötigen zusätzlich einen Administrator. Ohne gültige Lizenz
bleiben die bisherigen Ticketfunktionen verfügbar; Berechnung, Eskalationen und Exporte
sind gesperrt. E-Mails und Webhooks prüfen die Lizenz auch unmittelbar vor der Zustellung.

## Installation und Upgrade

1. Composer-Abhängigkeiten aktualisieren: zusätzlich `ext-zip` und `tecnickcom/tcpdf:^6.10`.
2. Contao-Cache neu aufbauen und die Contao-Datenbankmigration ausführen.
   `Version200Sla` ergänzt fehlende Tabellen und Spalten additiv und ist wiederholbar.
   Bestehende Tickets und SLA-Definitionen bleiben erhalten.
3. Im Backend unter **SLA → Kalender / Verträge / Prioritätsregeln** konfigurieren.
4. Den Contao-Minuten-Cronjob zuverlässig ausführen. Dieser berechnet Fristen,
   eskaliert, versendet Benachrichtigungen und verarbeitet die Webhook-Warteschlange.

Es werden keine produktiven Beispielverträge oder Eskalationsaktionen automatisch angelegt.

## Kalender

Ein Kalender enthält Zeitzone, Land (ISO-Code), Bundesland/Region, Wochenarbeitszeiten,
Feiertage und absolute Wartungsfenster. Mehrere Zeitfenster pro Tag sind möglich.
**24/7-Support** berücksichtigt alle Wochentage rund um die Uhr. Hinterlegte Feiertage
und Wartungsfenster bleiben dabei Ausnahmen; für durchgängigen 24/7-Betrieb leer lassen.

Feiertage werden als Datumszeilen gepflegt, etwa in einem Kalender `DE / BY`.
Es gibt keinen automatischen gesetzlichen Feiertagsabruf: Die Datumslisten müssen
für den benötigten Zeitraum gepflegt werden. Damit lassen sich auch lokale und
kundenspezifische Schließtage sowie beliebige Länder abbilden.

Wartungsfenster werden mit Beginn und Ende einschließlich Zeitzone eingegeben, etwa
`2026-10-05T10:00:00+02:00` bis `2026-10-05T12:00:00+02:00`. Überlappende Fenster
werden nur einmal abgezogen. Die Darstellung gespeicherter Fenster erfolgt in UTC.

Beispiel: Acht Supportstunden ab Freitag 17:00 bei Mo–Fr 09:00–18:00 enden am Montag
16:00. Ist Montag Feiertag, endet die Frist am Dienstag 16:00. Sommerzeitwechsel
werden über die Zeitzone berücksichtigt.

## Kundenverträge und Prioritäten

Ein Kunde entspricht einem Contao-Mitglied (`tl_member`). Ein Vertrag enthält
Vertragsnummer, Kunde, optionalen Service, SLA, optionalen Kundenkalender,
Vertragsbeginn/-ende, vereinbarte Laufzeit in Monaten, Kündigungsfrist in Kalendertagen
und Leistungsumfang. Das Ende ist eine **ausschließliche** Grenze; leer bedeutet
unbefristet. Laufzeit und Kündigungsfrist sind Vertragsinformationen; ein Vertragsende
wird ausdrücklich eingetragen, Kündigungen werden nicht automatisch ausgelöst.

Die Auswahl erfolgt anhand des Ticket-Anlagezeitpunkts:

1. Explizites SLA am Ticket.
2. Aktiver Vertrag für Kunde und Service.
3. Aktiver Kundenvertrag für alle Services.
4. Standard-SLA des Services.

Aktive Verträge desselben Kunden und Services dürfen sich zeitlich nicht überschneiden.
Die Fristen und der Kalender werden am Ticket eingefroren. Spätere Änderungen an
Vertrags- oder Kalenderdefinitionen ändern bestehende Fristen nicht. Eine Änderung
von Kunde, Service, SLA-Override oder Priorität löst eine Neuberechnung ab dem
ursprünglichen Anlagezeitpunkt aus. Bestehende SLA-Verletzungen bleiben historisch erhalten.

Prioritätsregeln werden je SLA für `critical`, `high`, `normal`, `low` hinterlegt.
Sie überschreiben Reaktions- und Lösungsbudget; ohne Regel gelten die SLA-Standardwerte.
Alle Budgets werden in **Supportminuten** angegeben. Ein „Tag“ muss vertraglich klar
in Supportstunden übersetzt werden, beispielsweise 8 Stunden = 480 Minuten.

Beispielwerte für Reaktion: kritisch 60, hoch 240, mittel 480, niedrig 1440 Minuten
(bei einem achtstündigen Supporttag). Vertragsklassen wie Standard, Business und Enterprise
werden als benannte SLA-Definitionen angelegt und Kundenverträgen zugeordnet.

## Eskalationen

Pro SLA gibt es drei Stufen: Teamleiter, Abteilungsleiter und Management.
Jede Stufe hat eine Verzögerung in Supportminuten ab der verletzten Reaktions- bzw.
Lösungsfrist, optionale E-Mail-Empfänger, eine HTTPS-Webhook-URL und einen optionalen
Zielstatus. Reaktionseskalationen enden mit der ersten öffentlichen Agentenantwort;
Lösungseskalationen enden mit Abschluss des Tickets.

Jede Stufe wird pro Ticket, SLA-Zyklus und Fristtyp nur einmal eingeplant. E-Mail und
Webhook können unabhängig voneinander oder zusammen mit einem Statuswechsel verwendet
werden. Zielstatus müssen veröffentlicht und offen sein: Eine Eskalation darf ein
Ticket nicht automatisch als gelöst deklarieren. Statusaktionen sind explizit vom
Administrator konfigurierte Systemaktionen und werden im Ticketjournal protokolliert.
Eine bereits erreichte höhere Stufe wird durch eine später fällige niedrigere Stufe
nicht zurückgesetzt.

Webhooks werden nach dem Commit zugestellt. Nur öffentliche HTTPS-Ziele sind erlaubt;
Weiterleitungen und private Netzadressen sind gesperrt. Fehlgeschlagene Zustellungen
werden mit wachsendem Abstand erneut versucht. Der Empfänger muss den stabilen Header
`Idempotency-Key` zur Duplikaterkennung verwenden (At-least-once-Zustellung).
Die Nutzdaten enthalten Ereignis, Ticket-ID/-Nummer, Stufe, Fristtyp und Fristzeitpunkt.
Die Zustellhistorie ist unter **SLA → Webhook-Zustellung** einsehbar.

## Dashboard und Monatsberichte

Das Dashboard zeigt Monatskennzahlen und einen aktuellen Monitor über alle Monate.
Monatsberichte enthalten Tickets, die im gewählten Monat angelegt wurden (Beginn
inklusive, Folgemonat exklusiv). Sie sind aktuelle Auswertungen dieser Kohorte, keine
unveränderlichen Monatsabschlussarchive.

- Ticketvolumen und Anzahl Tickets mit mindestens einer SLA-Verletzung.
- Erfüllungsquote: fristgerecht abgeschlossene / alle abgeschlossenen Tickets;
  ohne Abschlüsse wird kein Prozentwert ausgewiesen.
- Durchschnittliche Reaktions- und Lösungszeit in Supportzeit, einschließlich
  Stichprobengröße; ungelöste/unbeantwortete Tickets fließen nicht in den jeweiligen
  Durchschnitt ein. Pausen werden über die verschobenen Fristen berücksichtigt.
- Kategorien nach Ticketvolumen, Agentenstatistik anhand des aktuell zugewiesenen Agenten.
- Kritische Tickets: offen und Priorität kritisch oder noch unerledigte Frist
  innerhalb der nächsten 60 Echtzeitminuten bzw. bereits überschritten.
- Offene Eskalationen: ausgelöste Stufen des aktuellen SLA-Zyklus, deren Fristtyp
  noch nicht erledigt ist. Die Zahl zählt Stufen/Ereignisse, nicht eindeutige Tickets.

Die Monitorlisten sind auf 200 Einträge begrenzt; Kennzahlen und Monatsstatistiken
berücksichtigen den gesamten gefilterten Bestand. PDF, echtes Excel (`.xlsx`) und
UTF-8-CSV enthalten die Monatskennzahlen, Kategorien und Agentenstatistik.

CLI-Beispiele:

```sh
php bin/console issue:sla:report --month=2026-09
php bin/console issue:sla:report --month=2026-09 --format=pdf --output=/tmp/sla-2026-09.pdf
php bin/console issue:sla:report --month=2026-09 --format=xlsx --output=/tmp/sla-2026-09.xlsx
php bin/console issue:sla:report --month=2026-09 --format=csv --output=/tmp/sla-2026-09.csv
```

Bestehende Exportdateien werden nicht überschrieben. Alternativ zu `--month` stehen
weiterhin `--from` und `--until` zur Verfügung. Automatischer Versand von Berichten
ist nicht eingerichtet; die Dateien können über einen eigenen Scheduler erzeugt werden.
