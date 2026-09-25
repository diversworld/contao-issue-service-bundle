# Ticketbearbeitung und Journal

[Wiki-Start](Home.md)

## Bearbeitung im Backend

Unter **Service Management → Issues** Tickets öffnen. Das Dashboard bietet zusätzlich eine Übersicht mit Verweisen auf die Backend-Ticketbearbeitung. Service, Kategorie, Priorität, Bearbeiter, Status und Lösung beschreiben den Bearbeitungsstand.

Das Statusfeld enthält den aktuellen Status und die für den angemeldeten Benutzer erlaubten Ziele. Ein Wechsel wird erst nach erfolgreicher Formularprüfung durchgeführt. Bei paralleler Statusänderung wird der Konflikt gemeldet; das Ticket neu laden und die Änderung erneut prüfen.

## Notizen und Antworten

Im Abschnitt **Journal und Antworten** eine neue Notiz eingeben und die Sichtbarkeit wählen:

| Sichtbarkeit | Ergebnis |
| --- | --- |
| Nur intern im Backend | Bearbeitungsnotiz für Backend-Nutzer; keine öffentliche Antwortbenachrichtigung |
| Im Frontend-Verlauf sichtbar | Öffentliche Antwort, auch für den Ticketinhaber sichtbar; Benachrichtigungen gemäß Einstellungen |

Die Notiz wird mit dem Ticket gespeichert. Öffentliche Frontend-Antworten erscheinen ebenfalls im Backend-Journal. Statusänderungen, Zuweisungen, Prioritätsänderungen und Änderungen der Lösung werden als Ereignisse protokolliert. Eine erforderliche öffentliche Antwort kann nicht durch eine interne Notiz ersetzt werden.

## Anhänge und Zuweisung

Im Journalbereich stehen die Anhänge mit Download- und Anzeigelink zur Verfügung. Eine neue Bearbeiterzuweisung löst bei aktivierten Benachrichtigungen Mails an die konfigurierten Empfänger und den neuen Bearbeiter aus. Die Empfänger können durch persönliche Einstellungen eingeschränkt sein.

## Rollen und Grenzen

Backend-Modulrechte regeln den Zugang zur Bearbeitung; Workflowrollen regeln erlaubte Statuswechsel. Auch Administratoren unterliegen den Übergangsregeln. Eine direkte Wiederherstellung alter Ticketversionen ist gesperrt, da sie die Workflowprüfung umgehen würde.

Siehe [Workflow und Rollen](Workflow.md) für Einrichtung, Wiederöffnen und Beispielregeln.
