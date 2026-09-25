# Services, Kategorien und Konfigurationsprofile

[Wiki-Start](Home.md)

## Services und Kategorien

Services gliedern Aufgabenbereiche. Ein Service besitzt Titel, Alias, Beschreibung, Veröffentlichung, Standardpriorität, optionalen Standard-Bearbeiter und Benachrichtigungsempfänger. Für Empfänger eine Adresse pro Zeile verwenden. Ältere JSON-Listen werden ebenfalls gelesen.

Kategorien gehören jeweils zu einem Service. Sowohl Frontend als auch Backend berücksichtigen diese Zuordnung. Nach einem Servicewechsel muss eine passende Kategorie gewählt werden; im Frontend ist auch „Keine Kategorie“ möglich.

## Konfigurationsprofile

Unter **Service Management → Konfigurationsprofile** wird jede Konfiguration als ein Datensatz mit eigenem Titel gespeichert. Das Erstellungsmodul wählt ein Profil; am Ticket wird dessen ID gespeichert. Damit gelten beispielsweise Uploadgrenzen und Wiederöffnungsregeln weiterhin für dieses Ticket. Änderungen am Profil beeinflussen die daraus gelesenen Einstellungen bestehender Tickets; ein Profil ist kein unveränderlicher Schnappschuss.

| Bereich | Einstellung und Wirkung |
| --- | --- |
| Bezeichnung | Titel und Beschreibung zur Unterscheidung der Profile |
| Ticketnummer | Muster mit `{SERVICE}`, `{YEAR}` und `{SEQ}`; Sequenz ist erforderlich |
| Anmeldung | Feld „Login erforderlich“ ist ein Altparameter; die gelieferten Frontendabläufe setzen immer eine Anmeldung voraus |
| Dateitypen | Zulässige Upload-Endungen; eine leere Auswahl erlaubt keine Uploads |
| Dateigröße | Maximum je Datei in Bytes; Vorgabe 10 MiB |
| Anzahl Anhänge | Maximum über das gesamte Ticket einschließlich später ergänzter Dateien; Vorgabe 5 |
| Ablage | `var` mit optionalem Unterverzeichnis oder ein ausgewählter Ordner unter `files` |
| Benachrichtigungen | Empfängeradressen und vier Ereignisschalter |
| Aufbewahrung | Frist für geschlossene Tickets; Vorgabe 730 Tage |
| Wiederöffnen | Zusätzlich zu Workflowregeln erlaubte Rollen |
| Servicebezogene Berechtigungen | Workflowrollen nur für zugeordnete Services anwenden |

Ein explizit ausgewähltes Profil hat bei der Anhangsablage Vorrang vor den Ablagefeldern des Erstellungsmoduls. Ohne explizite Profilwahl nutzt das Modul seine Ablagefelder; die übrigen Einstellungen fallen auf das Standardprofil zurück. Deshalb für nachvollziehbare Konfigurationen immer ein Profil auswählen. Als Standardprofil dient der erste Profildatensatz; alte Schlüssel-Wert-Einstellungen werden als Fallback unterstützt.

Mehrere Profile eignen sich für unterschiedliche Aufgaben und Organisationseinheiten. Sie isolieren jedoch weder Stammdaten noch Backend-Ticketlisten als getrennte Mandanten. Workflowregeln gelten profilübergreifend.

## Automatische Aliasse

Bei Services und Kategorien kann das Aliasfeld leer bleiben; beim Speichern wird es aus dem Titel erzeugt. Service-Aliasse sind global eindeutig, Kategorie-Aliasse innerhalb ihres Services. Bei automatisch erzeugten Duplikaten ergänzt Contao eine Nummer. Manuell gesetzte Aliasse bleiben erhalten und werden auf Eindeutigkeit geprüft. Beim Kopieren wird ein neuer Alias erzeugt.

Auch ein leerer Statusschlüssel wird aus dem Titel erzeugt. Bestehende Schlüssel werden nicht automatisch geändert. Die Standardstatusschlüssel wie `new`, `resolved` und `closed` beibehalten, da Anwendungslogik und Beispielmigrationen sie verwenden. Tickets selbst haben kein Aliasfeld; sie werden über UUID und Ticketnummer identifiziert.
