# Templates, Entwicklung und Tests

[Wiki-Start](Home.md)

## Aufbau

| Pfad | Inhalt |
| --- | --- |
| `contao/dca` | Backend-Felder, Paletten und Tabellenbeschreibung |
| `contao/languages` | Backend- und Frontend-Beschriftungen |
| `src/FrontendModule` | Contao-Frontend-Module |
| `src/Controller` | Formular-, Detail- und Downloadrouten |
| `src/Application` | Ticket-, Workflow-, Kommentar-, Upload- und Benachrichtigungslogik |
| `src/Repository` | Datenbankzugriffe |
| `src/Security` | Rollen und objektbezogene Zugriffsprüfung |
| `src/EventListener` | DCA-Callbacks und automatische Verarbeitung |
| `src/Migration` | Schema- und Datenmigrationen |
| `public` | Bundle-CSS und JavaScript |

## Templates und Gestaltung

Die Contao-Modultemplates liegen in `contao/templates/twig/frontend_module/`:

- `issue_service_list.html.twig`
- `issue_service_create.html.twig`
- `issue_service_detail.html.twig`

Die Controller verwenden zusätzlich Templates unter `templates/issue/`, insbesondere `create.html.twig` und `detail.html.twig`. Bei Änderungen beide Darstellungswege berücksichtigen. Die Fehlerbeschreibung wird in den Detailtemplates mit `striptags|nl2br` als Text ohne HTML-Tags dargestellt.

Modultemplates lassen sich über Contaos Templateverwaltung beziehungsweise `customTpl` anpassen. Das Detailtemplate bietet Blöcke wie `detail`, `specification`, `attachment`, `timeline` und `reply`. Gemeinsame Bundlestile liegen in `public/css/issue-service.css`; projektspezifische Theme-SCSS-Anpassungen gehören in das Theme. Die serviceabhängige Kategorieauswahl verwendet `public/js/issue-create.js`.

## Tests

Nach Installation der Entwicklungsabhängigkeiten im Bundle:

```bash
composer test
composer analyse
```

`composer check` führt beide Schritte aus. In einer eingebundenen Entwicklungskopie kann der Autoloader des Contao-Projekts erforderlich sein. Beispiel für die lokale DDEV-Struktur:

```bash
ddev exec bash -c 'cd /home/diversworld/sources/contao-issue-service-bundle && php vendor/bin/phpunit --bootstrap /var/www/html/vendor/autoload.php'
```

Pfade an das eigene Projekt anpassen. Die Tests decken unter anderem Workflowberechtigungen, Pflichtantworten, Benachrichtigungspräferenzen, Wiederholungsversand, Formulare und Migrationen ab. Sie ersetzen keine vollständigen Browser- und Upgradeprüfungen auf jeder unterstützten Contao-Version.

Für eine Abnahme zusätzlich prüfen: Erstellung mit Anhang, eigene/fremde Ticketzugriffe, interne/öffentliche Antworten, Statuswechsel mit verschiedenen Rollen, Servicewechsel, Profilbearbeitung, Download/Vorschau und tatsächlich konfigurierter Mailtransport. Schemaänderungen an leerer und vorhandener Datenbank testen.

Der [Kompatibilitätsbericht](../COMPATIBILITY_AUDIT.md) dokumentiert einen früheren Prüfstand. Er ist keine laufende Zertifizierung späterer Änderungen.
