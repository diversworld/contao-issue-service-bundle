# Kompatibilitätsprüfung Contao 5.7 / 6

## Ergebnis der statischen Prüfung

Status: statisch und in der lokalen DDEV-Integration für Contao 5.7.13 sowie per isolierter Composer-Auflösung für Contao 6.0.0 verifiziert.

### Korrigiert

1. Manager Plugin nicht mehr als produktive Pflichtabhängigkeit. Gemäß Contao-Dokumentation wird `contao/manager-plugin` in `require-dev` geführt und über `conflict` auf Major 2 begrenzt.
2. Pakettyp bleibt `contao-bundle` und der Manager-Plugin-FQCN steht unter `extra.contao-manager-plugin`.
3. Backend-Route verwendet `%contao.backend.route_prefix%` und `defaults: ['_scope' => 'backend']`.
4. Controller werden über Attribute-Routing importiert.
5. DCA liegt unter `contao/dca`, beginnt mit `tl_` und enthält `config`, `list`, `palettes` und `fields`.
6. Security-Design verwendet objektbezogene Policies/Voter statt alleiniger UUID-Prüfung.
7. CI-Matrix trennt Contao 5.7 und Contao 6 sowie niedrige und hohe Abhängigkeitsauflösung.

### Verifiziert am 24. September 2026

- Composer-Auflösung mit Contao 5.7.13 und Contao 6.0.0.
- PHPUnit: 19 Tests und 37 Assertions auf beiden Abhängigkeitslinien.
- PHPStan Level 8 ohne Fehler auf beiden Abhängigkeitslinien.
- Contao-5.7-DDEV: Container-Lint, sechs Bundle-Routen und Schema-Dry-Run ohne ausstehende Bundle-Änderungen.
- Migration und DCA-Schema gegen MariaDB 11.8; alle internen Tabellen bleiben bei `contao:migrate --with-deletes` registriert.
- Frontend-Routen verwenden den Contao-Scope `frontend`, die Backend-Route den Scope `backend`.

### Verbleibende Systemprüfung

- Browser-Smoke-Tests mit angemeldetem Frontend-Mitglied und Backend-Benutzer.
- Produktive Mailer-, Berechtigungs- und Upload-Konfiguration einschließlich Malware-Scanner.
- Vollständiger Cache-Warmup nach Reparatur des unabhängigen Pakets `codefog/contao-news_categories`, dessen fehlendes `contao/dca`-Verzeichnis den globalen Warmup derzeit blockiert.

## Release-Gates

- `composer update --prefer-lowest` und normale Auflösung für beide Contao-Linien erfolgreich.
- PHPUnit und PHPStan grün.
- `debug:router`, `debug:container` und `contao:migrate` erfolgreich.
- Frontend- und Backend-Smoke-Tests in beiden Linien erfolgreich.
- Keine unbehandelten Deprecations im Contao-5.7-Lauf.
