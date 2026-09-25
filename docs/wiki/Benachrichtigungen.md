# Ticketbenachrichtigungen

[Wiki-Start](Home.md)

Bei der Erstellung erhalten die im Ticketprofil und im Service eingetragenen Empfänger sowie der Standard-Bearbeiter eine Nachricht. Bei einer Änderung der Bearbeiterzuweisung erhalten die konfigurierten Empfänger und der neu zugewiesene Bearbeiter eine Nachricht. Die Bearbeiteradresse stammt aus dessen Backend-Benutzerkonto. Doppelte Adressen werden je Ereignis zusammengefasst. Eine entfernte Zuweisung informiert weiterhin die konfigurierten Empfänger.

Service-Empfänger können zeilenweise oder als bestehendes JSON-Array gespeichert sein. Im Profil wird eine Adresse pro Zeile eingetragen. Ungültige Adressen werden übersprungen. Ohne gültige Empfänger entsteht kein Warteschlangeneintrag.

Nach Abschluss einer Anfrage mit neuen Benachrichtigungen werden bis zu 50 fällige Nachrichten versendet. Ein minütlicher Contao-Cronjob verarbeitet weitere Nachrichten und wiederholt fehlgeschlagene Versuche mit wachsendem Abstand. Bei Contaos aufrufabhängigem Cron hängt die Ausführung von Seitenaufrufen ab; für regelmäßige Verarbeitung ohne Besucher muss der Contao-Cron serverseitig laufen.

Absender ist standardmäßig die Administrator-E-Mail aus den Contao-Einstellungen. Der Parameter `contao_issue_service.mail_from` kann sie überschreiben. Der Versand verwendet den Symfony-/Contao-Mailer und den dort konfigurierten Transport. In der üblichen DDEV-Konfiguration erscheinen Nachrichten in Mailpit statt in externen Postfächern (`ddev mailpit`).

Die Tabelle `tl_issue_notification` enthält den Versandstatus und bei Fehlern `last_error`, `attempt_count` und `next_attempt_at`. Der bestehende Befehl `issue-service:notifications:dispatch` verarbeitet die fällige Warteschlange auch manuell. Ein Versand-Lock verhindert parallele Verarbeitung durch Cron, Anfrage und Befehl.

## Ereignisse und persönliche Einstellungen

Unter **Konfigurationsprofile → Benachrichtigungen** lassen sich Ticketerstellung, Bearbeiterzuweisung, Statusänderung und öffentliche Antwort einzeln ein- und ausschalten. Bestehende Profile behalten alle vier Ereignisse aktiviert.

Backend-Benutzer finden dieselben Schalter unter **Persönliche Daten → Ticketbenachrichtigungen**; Administratoren können sie auch in der Benutzerverwaltung bearbeiten. Standardmäßig sind alle vier aktiviert. Zugewiesene Bearbeiter werden bei allen aktivierten Ereignissen berücksichtigt.

Frontend-Mitglieder können die vier Schalter im Contao-Modul **Persönliche Daten** bearbeiten. Dazu in dessen Modulkonfiguration die vier neuen bearbeitbaren Felder auswählen. Sie sind auch unter **Mitglieder → Ticketbenachrichtigungen** verfügbar. Für Mitglieder sind sie zunächst ausgeschaltet; nach Aktivierung erhalten Mitglieder Nachrichten ausschließlich für ihre eigenen Tickets.

Persönliche Abmeldungen haben Vorrang, auch wenn dieselbe Adresse zusätzlich in den Service-/Profilempfängern eingetragen ist. Ein global im Ticketprofil ausgeschaltetes Ereignis kann persönlich nicht eingeschaltet werden. Die Einstellungen werden vor dem Einreihen und erneut unmittelbar vor dem Versand geprüft. Nicht mehr gewünschte Warteschlangeneinträge erhalten den Status `skipped`.

Die Migration `Version123NotificationPreferences` ergänzt die Felder beim Contao-Datenbankupdate, ohne bestehende Einstellungen zu überschreiben.
