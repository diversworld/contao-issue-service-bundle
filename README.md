<<<<<<< HEAD
# Contao Issue & Service Management Bundle

Implementierungsstand für Contao 5.7 und Contao 6 auf Basis des Pflichtenhefts.

## Enthalten

- Vollständige Tabellenmigration für Services, Kategorien, Status, Übergänge, Issues, Kommentare, Anhänge, Historie, Einstellungen, Servicegruppen und Benachrichtigungs-Outbox
- Application Services für Erstellung, Workflow, Kommentare, Anhänge, Einstellungen, Retention und Benachrichtigungen
- DBAL-Repositories und transaktionale Schreibvorgänge
- Frontend-Controller für Liste, Erfassung, Detail, Kommentar, Wiederöffnung und Download
- Symfony Forms, CSRF-Prüfung und objektbezogene Voter
- Backend-DCA für Stammdaten und Einstellungen
- Konsolenbefehle für Retention und Notification-Retry
- Twig-Templates, Übersetzungen, Testskelett und CI-Matrix

## Installation

```bash
composer require vendor/contao-issue-service-bundle
php vendor/bin/contao-console contao:migrate
php vendor/bin/contao-console cache:clear
```

Danach müssen im Backend die Pflichtwerte unter Service Management / Einstellungen konfiguriert werden.

## Wichtige Hinweise

Der Paketname `vendor/...`, Absenderadressen und organisationsspezifische Werte sind vor Veröffentlichung anzupassen. Composer-Auflösung, Functional Tests und Migrationen müssen im konkreten Contao-5.7- und Contao-6-Zielprojekt ausgeführt werden. Die lokale Erstellung dieses Pakets prüft PHP-Syntax und Archivintegrität, ersetzt aber keinen Lauf in einer echten Contao-Installation.
=======
# Contao Issue Service Bundle - Test- und Kompatibilitätspaket

Dieses Paket ergänzt automatisierte Unit- und Strukturtests sowie eine dokumentierte Kompatibilitätsprüfung für Contao 5.7 und Contao 6.

```bash
composer update
composer check
php vendor/bin/contao-console contao:migrate --no-interaction
```

Siehe `docs/TESTPLAN.md`, `docs/COMPATIBILITY_AUDIT.md` und `docs/REMAINING_INTEGRATION_STEPS.md`.
>>>>>>> 0058e26 (Erster Commit)
