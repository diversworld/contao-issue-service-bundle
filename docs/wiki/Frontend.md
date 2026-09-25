# Frontend-Module und Mitgliederportal

[Wiki-Start](Home.md)

## Seiten einrichten

Unter den Contao-Frontend-Modulen stehen diese Typen bereit:

| Typ | Verwendung | Wichtige Konfiguration |
| --- | --- | --- |
| `issue_service_list` | Eigene Tickets anzeigen | Weiterleitungsseite mit Detailmodul |
| `issue_service_create` | Neues Ticket erfassen | Konfigurationsprofil und Weiterleitungsseite |
| `issue_service_detail` | Ticket, Verlauf, Anhänge und Antwortformular | Auf der gemeinsamen Ticketdetailseite einbinden |

Die Module über Inhaltselemente in passende Artikel einbinden und eine Contao-Anmeldung bereitstellen. In Liste und Erstellung `jumpTo` auf die Detailseite setzen. Der Link übergibt die Ticket-UUID. Ohne Weiterleitungsseite versucht das Bundle eine Seite mit Detailmodul zu ermitteln und verwendet andernfalls seine Controllerroute. Bei mehreren Portalen die Zielseite ausdrücklich konfigurieren.

## Ticket erstellen

Das Formular enthält Service, serviceabhängige Kategorie, Typ, Priorität, Titel, Beschreibung und Anhänge. Verfügbare Typen sind Störung, Fehler, Verbesserung, Anfrage und Frage; Prioritäten sind Niedrig, Normal, Hoch und Kritisch. Neue Tickets beginnen im Status „Neu“. Der Service kann einen Standard-Bearbeiter liefern.

## Liste und Detail

Mitglieder sehen nur ihre eigenen, nicht gelöschten Tickets. Die Liste zeigt aktuell höchstens 20 Einträge, sortiert nach letzter öffentlicher Aktivität. Eine frei einstellbare Seitennavigation ist im Listenmodul nicht implementiert.

Die Detailansicht zeigt Ticketdaten, die Beschreibung ohne HTML-Tags, den öffentlichen Verlauf sowie Anhänge mit Download und Vorschau. Mitglieder können antworten, Anhänge nachreichen oder beides zusammen senden. Ein Anhang kann ohne zusätzlichen Antworttext ergänzt werden. Das Dateilimit gilt weiterhin für das gesamte Ticket.

Erlaubte Statuswechsel erscheinen zusätzlich im Antwortformular. Eine reine Antwort ändert den Status nicht automatisch. Bei einem Übergang mit Pflichtantwort ist ein öffentlicher Text erforderlich. Interne Backend-Notizen werden Mitgliedern nicht angezeigt.

## Persönliche Benachrichtigungen

Ein Contao-Modul **Persönliche Daten** bereitstellen. In dessen Auswahl der bearbeitbaren Felder die vier Ticketbenachrichtigungen aktivieren: Erstellung, Zuweisung, Statusänderung und öffentliche Antwort. Die Felder gehören im Frontend zur Kontaktgruppe. Mitglieder können sie dann für eigene Tickets selbst setzen; initial sind sie ausgeschaltet.

Details zu globalen und persönlichen Freigaben stehen unter [Benachrichtigungen](Benachrichtigungen.md).
