# Workflow und Rollen

## Einrichtung

1. Unter **Benutzergruppen → Ticket-Workflow** die Rolle `agent` (Bearbeiter) oder `manager` (Verantwortlicher) auswählen. Benutzer über Contaos normale Gruppenmitgliedschaft zuordnen. Die Gruppe bzw. der Benutzer benötigt weiterhin Zugriff auf das Backend-Modul „Issues“.
2. Die Services der Gruppe auswählen. Ist „Servicebezogene Berechtigungen“ im Ticketprofil aktiv, gilt die Rolle nur für diese Services. Eine leere Serviceauswahl gewährt dann keine Rolle. Ohne diese Profileinstellung gilt die Gruppenrolle für alle Services.
3. Unter **Service Management → Workflow → Workflow-Regeln** auf **Neu** klicken und Übergänge mit Ausgangsstatus, Zielstatus, Rolle und gegebenenfalls öffentlicher Pflichtantwort anlegen und aktivieren. Auch der Zielstatus muss veröffentlicht sein. Ohne aktivierte Regel ist kein Wechsel erlaubt.
4. Im Konfigurationsprofil die Rollen zum Wiederöffnen auswählen. Diese Freigabe gilt zusätzlich zur Übergangsregel für Wechsel aus gelösten oder geschlossenen Tickets zurück in einen offenen Status.

## Verhalten

- Die Rollen mehrerer aktiver Gruppen werden zusammengeführt. `manager` erbt nicht automatisch `agent`.
- Administratoren erhalten `agent` und `manager`, umgehen jedoch weder Übergangsregeln noch Pflichtantworten oder Wiederöffnungsregeln.
- Ein Frontend-Mitglied erhält `member` ausschließlich für seine eigenen Tickets. Erlaubte Statuswechsel erscheinen im Antwortformular. Für Mitglieder sind Antworten immer öffentlich.
- Im Backend zeigt das Statusfeld den aktuellen Status und erlaubte Ziele. Bei einer Pflichtantwort muss im Journal eine Notiz eingegeben und „Im Frontend-Verlauf sichtbar“ gewählt werden. Interne Notizen erfüllen diese Pflicht nicht.
- Statuswechsel werden erst nach erfolgreicher Formularprüfung durchgeführt. Die zentrale Verarbeitung prüft erneut, sperrt den Datensatz während der Transaktion und aktualisiert Historie, Benachrichtigungen, Version und Zeitstempel.
- Neue Tickets beginnen mit „Neu“. Eine direkte Wiederherstellung alter Ticketversionen ist gesperrt, da sie den Workflow umgehen würde. Änderungen müssen über die Ticketbearbeitung erfolgen.
- Servicebezogene Rollen begrenzen Workflow-Aktionen; sie ersetzen keine separate Mandantenisolierung für Ticketlisten oder Downloads.

Die Installation vergibt keine Gruppenrollen und erzeugt keine Übergangsregeln automatisch. Bestehende Zuordnungen in `tl_issue_service_group` werden zusätzlich berücksichtigt.

## Status und Regeln im Backend

Der Menüpunkt **Workflow** öffnet zunächst die Statusliste. Der Button **Workflow-Regeln** oberhalb der Liste führt zur Regelverwaltung. Dort öffnet **Neu** das Formular mit Ausgangsstatus, Zielstatus, Rolle, öffentlicher Pflichtantwort und Aktivierung. Über **Status verwalten** geht es zurück zur Statusliste. Ausgangs- und Zielstatus müssen unterschiedlich sein; je Kombination aus beiden Status und Rolle ist nur eine Regel erlaubt.

Regeln gelten für alle Ticketprofile. Gruppenrollen und Servicezuordnungen sowie die Wiederöffnungsfreigabe des jeweiligen Profils schränken ihre Anwendung ein.

## Beispielregeln

[examples/workflow.sql](examples/workflow.sql) legt 23 aktive Regeln für die vorhandenen Standardstatus an. Bereits vorhandene Regeln bleiben unverändert. Jede angegebene Rolle erhält einen eigenen Datensatz.

| Ausgangsstatus | Zielstatus | Rollen | Öffentliche Antwort erforderlich |
| --- | --- | --- | --- |
| Neu | In Prüfung | agent, manager | Nein |
| Neu | In Bearbeitung | agent, manager | Nein |
| In Prüfung | In Bearbeitung | agent, manager | Nein |
| In Prüfung | Abgelehnt | agent, manager | Ja |
| In Bearbeitung | Rückfrage | agent, manager | Ja |
| Rückfrage | In Bearbeitung | agent, manager | Nein |
| Rückfrage | In Bearbeitung | member | Ja |
| In Bearbeitung | Gelöst | agent, manager | Ja |
| Gelöst | Geschlossen | agent, manager, member | Nein |
| Gelöst | In Bearbeitung | agent, manager, member | Ja |
| Geschlossen | In Bearbeitung | manager | Ja |
| Abgelehnt | In Prüfung | manager | Ja |

Auch diese Beispielregeln umgehen keine Profil- oder Serviceberechtigung. Eine normale Antwort ohne ausgewählten Zielstatus ändert den Ticketstatus nicht automatisch.
