# Workflow und Rollen

## Einrichtung

1. Unter **Benutzergruppen → Ticket-Workflow** die Rolle `agent` (Bearbeiter) oder `manager` (Verantwortlicher) auswählen. Benutzer über Contaos normale Gruppenmitgliedschaft zuordnen. Die Gruppe bzw. der Benutzer benötigt weiterhin Zugriff auf das Backend-Modul „Issues“.
2. Die Services der Gruppe auswählen. Ist „Servicebezogene Berechtigungen“ im Ticketprofil aktiv, gilt die Rolle nur für diese Services. Eine leere Serviceauswahl gewährt dann keine Rolle. Ohne diese Profileinstellung gilt die Gruppenrolle für alle Services.
3. Unter **Service Management → Workflow** Übergänge mit Ausgangsstatus, Zielstatus, Rolle und gegebenenfalls öffentlicher Pflichtantwort anlegen und aktivieren. Auch der Zielstatus muss veröffentlicht sein. Ohne aktivierte Regel ist kein Wechsel erlaubt.
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
