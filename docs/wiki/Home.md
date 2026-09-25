# Wiki: Contao Issue & Service Management

Das Bundle verbindet ein Mitglieder-Ticketportal mit der Bearbeitung im Contao-Backend. Dieses Wiki beschreibt den implementierten Stand und dient als Einstieg für Administration, Bearbeitung und Entwicklung.

## Inhalt

1. [Installation und erster Start](Installation.md)
2. [Services, Kategorien und Konfigurationsprofile](Konfiguration.md)
3. [Frontend-Module und Mitgliederportal](Frontend.md)
4. [Ticketbearbeitung und Journal](Ticketbearbeitung.md)
5. [Workflow und Rollen](Workflow.md)
6. [Benachrichtigungen und persönliche Auswahl](Benachrichtigungen.md)
7. [Anhänge und Zugriffsgrenzen](Anhaenge-und-Zugriff.md)
8. [Betrieb, Migrationen und Fehlerbehebung](Betrieb.md)
9. [Templates, Entwicklung und Tests](Entwicklung.md)

## Begriffe

| Begriff | Bedeutung |
| --- | --- |
| Service | Zuständiger Aufgabenbereich mit Kategorien und optionalem Standard-Bearbeiter |
| Kategorie | Einordnung innerhalb eines Services |
| Konfigurationsprofil | Benannter Satz gemeinsamer Einstellungen, etwa Uploadgrenzen und Benachrichtigungen |
| Mitglied | Angemeldeter Contao-Frontend-Nutzer; sieht eigene Tickets |
| Bearbeiter | Contao-Backend-Benutzer mit passenden Modulrechten und Workflowrolle |
| Workflowregel | Erlaubter Übergang von einem Status zu einem anderen für eine Rolle |
| Journal | Chronologischer Verlauf aus Ereignissen, öffentlichen Antworten und internen Notizen |

Für die Ersteinrichtung mit [Installation](Installation.md) beginnen. Die kurze Paketübersicht steht in der [README](../../README.md).
