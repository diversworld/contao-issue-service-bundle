# Anhänge und Zugriffsgrenzen

[Wiki-Start](Home.md)

## Ablage wählen

| Ablage | Verhalten |
| --- | --- |
| `var` | Standardwurzel `var/issue-attachments`; nicht direkt öffentlich erreichbar. Auslieferung über geprüfte Downloadrouten. |
| `files` | Admin wählt einen bestehenden Ordner in der Contao-Dateiverwaltung. In öffentlichen Ordnern können direkte Datei-URLs die Ticketzugriffsprüfung umgehen. |

Das Konfigurationsprofil bestimmt die Ablage, sofern es im Erstellungsmodul ausdrücklich gewählt wurde. Ohne Profilwahl stehen Ablagefelder am Erstellungsmodul bereit. Das optionale Unterverzeichnis in `var` ist relativ; erlaubt sind Buchstaben, Zahlen, Bindestriche, Unterstriche und weitere Verzeichnisebenen.

Ablageinformationen werden am Ticket beziehungsweise am Anhang gespeichert. Eine spätere Profiländerung verschiebt vorhandene Dateien nicht automatisch. Den zugehörigen Ordner weiterhin verfügbar halten und gemeinsam mit der Datenbank sichern.

## Upload und Anzeige

Dateityp, zur Endung passender Inhalt, Größe und maximale Gesamtzahl werden geprüft. Dateien erhalten generierte Speichernamen; der ursprüngliche Dateiname wird für die Anzeige gespeichert. Bei Ablage unter `files` werden Dateien zusätzlich in der Contao-Dateiverwaltung registriert.

Die Detailansicht und das Backend bieten Download sowie Anzeige in einem neuen Tab. Geeignete PDF- und Bildformate können inline angezeigt werden; andere Formate werden heruntergeladen. Die Vorschau ist keine allgemeine Office-Dokumentkonvertierung.

## Zugriffsmodell

Frontend-Mitglieder können ausschließlich eigene Tickets und deren freigegebene Anhänge abrufen. Backendzugriff folgt der Backend-Berechtigungsprüfung. Gastzugriff ist im Datenmodell vorbereitet, aber in den gelieferten Frontendabläufen nicht aktiviert.

Servicebezogene Workflowrollen und Konfigurationsprofile sind keine vollständige Mandantentrennung. Öffentliche `files`-Ordner sind für vertrauliche Ticketdokumente ungeeignet; hierfür die private Ablage wählen.

Ein Malware-Scanner ist nicht implementiert. Das Datenfeld `scan_status` ersetzt keinen Virenscan. Bei entsprechenden Betriebsanforderungen muss eine Scannerintegration ergänzt werden.
