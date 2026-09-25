[![License](https://img.shields.io/badge/license-LGPL--3.0--or--later-blue)](LICENSE)
[![Contao](https://img.shields.io/badge/Contao-5.7%2B%20%7C%206.0%2B-green)](https://contao.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple)](https://www.php.net)

[![Latest Version on Packagist](http://img.shields.io/packagist/v/diversworld/contao-issue-service-bundle.svg?style=flat)](https://packagist.org/packages/diversworld/contao-issue-service-bundle)
![Dynamic JSON Badge](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fdiversworld%2Fcontao-issue-service-bundle%2Fmain%2Fcomposer.json&query=%24.require%5B%22contao%2Fcore-bundle%22%5D&label=Contao%20Version)
[![Installations via composer per month](http://img.shields.io/packagist/dm/diversworld/contao-issue-service-bundle.svg?style=flat)](https://packagist.org/packages/diversworld/contao-issue-service-bundle)
[![Installations via composer total](http://img.shields.io/packagist/dt/diversworld/contao-issue-service-bundle.svg?style=flat)](https://packagist.org/packages/diversworld/contao-issue-service-bundle)
![Packagist License](https://img.shields.io/packagist/l/diversworld/contao-issue-service-bundle)

![Diversworld](docs/dw-logo-k.png "Diversworld Logo")

# Contao Issue & Service Management

Ticket- und Servicemanagement für Contao: Mitglieder erfassen und verfolgen eigene Tickets im Frontend; Bearbeiter verwalten sie im Backend mit Journal, Anhängen und rollenabhängigen Statuswechseln.

- Services, zugehörige Kategorien und benannte Konfigurationsprofile
- Ticketliste, Erstellungsformular und Detailansicht für angemeldete Mitglieder
- Öffentliche Antworten und interne Bearbeitungsnotizen
- Anhänge mit Download und Vorschau; Ablage in `var` oder `files`
- Workflowregeln, Gruppenrollen und 23 automatisch ergänzte Beispielregeln
- E-Mail-Benachrichtigungen mit persönlichen Einstellungen und Wiederholungsversand

## Installation

Composer-Paket: `diversworld/contao-issue-service-bundle`. Laut Paketdefinition: PHP ≥ 8.2 und Contao `^5.7 || ^6.0`; maßgeblich sind auch die Anforderungen der eingesetzten Contao-Version. Das Paket muss über ein eingerichtetes Composer-Repository verfügbar sein.

```bash
composer require diversworld/contao-issue-service-bundle
php vendor/bin/contao-console contao:migrate
php vendor/bin/contao-console cache:clear
```

Danach unter **Service Management** ein Konfigurationsprofil, Services und Kategorien einrichten, Benutzergruppen zuordnen und die drei Frontend-Module einbinden. In DDEV den Befehlen `ddev exec` voranstellen.

**[Zum Wiki: Einrichtung, Bedienung und Betrieb](docs/wiki/Home.md)**

Profile ermöglichen unterschiedliche Einstellungen, sind aber keine vollständige Mandantentrennung. Für private Anhänge `var` verwenden. Lizenz: proprietär, siehe [Paketdefinition](composer.json).


If you like this extension and think it's worth a little donation: You can support me via Paypal.Me:

[Donation for Diversworld DiveClubManager](https://paypal.me/EckhardBecker615)

Thank You!
