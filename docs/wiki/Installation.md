# Installation und erster Start

[Wiki-Start](Home.md)

## Voraussetzungen

Die [composer.json](../../composer.json) definiert PHP `>=8.2`, Contao `^5.7 || ^6.0` und Doctrine DBAL `^3.8 || ^4.0`. Diese Untergrenzen ersetzen nicht die Anforderungen der jeweils installierten Contao-Version. Die Datenbankmigrationen verwenden MySQL-/MariaDB-Syntax.

Das Paket ist ein Contao-Bundle mit Manager-Plugin. Es muss für Composer verfügbar sein, beispielsweise über das eigene VCS- oder ein lokales Path-Repository. Eine öffentliche Packagist-Verfügbarkeit wird hier nicht vorausgesetzt.

## Paket installieren

Im Contao-Projekt ausführen:

```bash
composer require diversworld/contao-issue-service-bundle
php vendor/bin/contao-console contao:migrate
php vendor/bin/contao-console cache:clear
```

In DDEV entsprechend:

```bash
ddev composer require diversworld/contao-issue-service-bundle
ddev exec vendor/bin/contao-console contao:migrate
ddev exec vendor/bin/contao-console cache:clear
```

Vor Updates Datenbank und Anhangsablage sichern. Bei lokaler Bundleentwicklung muss das Path-Repository in der Projektkonfiguration auf den tatsächlichen Quellordner zeigen.

## Erstkonfiguration

1. **Service Management → Konfigurationsprofile:** ein benanntes Profil anlegen und Ticketnummer, Anhänge, Benachrichtigungen und Aufbewahrung festlegen.
2. **Services:** mindestens einen veröffentlichten Service anlegen; optional Standard-Bearbeiter und zusätzliche E-Mail-Empfänger festlegen.
3. **Kategorien:** Kategorien dem Service zuordnen und veröffentlichen. Kategorieauswahlen werden nach Service gefiltert.
4. **Workflow:** Status und automatisch ergänzte Beispielregeln prüfen. Gruppenrollen werden nicht automatisch vergeben.
5. **Benutzergruppen:** Zugriff auf das Backend-Modul und Rolle/Services im Abschnitt „Ticket-Workflow“ vergeben; Bearbeiter diesen Gruppen zuordnen.
6. **Frontend:** Anmeldung sowie Liste, Erstellung und Detailseite einrichten. Dem Erstellungsmodul das gewünschte Konfigurationsprofil zuweisen.
7. **E-Mail:** Contao-Administratoradresse, Mailtransport und Empfänger prüfen. Für Mitglieder die persönlichen Benachrichtigungsfelder freigeben.
8. Mit einem Mitglied ein Ticket erstellen, im Backend bearbeiten und mit einem anderen Mitglied die Zugriffstrennung prüfen.

Die [Workflow-Anleitung](Workflow.md) erklärt die Rollen; die [Frontend-Anleitung](Frontend.md) die Seitenverknüpfung.
