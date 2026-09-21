# Kompatibilitätsprüfung Contao 5.7 / 6

## Ergebnis der statischen Prüfung

Status: bedingt kompatibel, nicht produktionsfreigegeben.

### Korrigiert

1. Manager Plugin nicht mehr als produktive Pflichtabhängigkeit. Gemäß Contao-Dokumentation wird `contao/manager-plugin` in `require-dev` geführt und über `conflict` auf Major 2 begrenzt.
2. Pakettyp bleibt `contao-bundle` und der Manager-Plugin-FQCN steht unter `extra.contao-manager-plugin`.
3. Backend-Route verwendet `%contao.backend.route_prefix%` und `defaults: ['_scope' => 'backend']`.
4. Controller werden über Attribute-Routing importiert.
5. DCA liegt unter `contao/dca`, beginnt mit `tl_` und enthält `config`, `list`, `palettes` und `fields`.
6. Security-Design verwendet objektbezogene Policies/Voter statt alleiniger UUID-Prüfung.
7. CI-Matrix trennt Contao 5.7 und Contao 6 sowie niedrige und hohe Abhängigkeitsauflösung.

### Noch nicht abschließend verifiziert

- Reale Composer-Auflösung, da in der Erstellungsumgebung kein PHP-/Composer-Lauf verfügbar ist.
- Exakte PHP-Untergrenze der konkret eingesetzten Contao-5.7- und Contao-6-Releases.
- Laufzeitkompatibilität von Templates, Backend-Menü und DCA in realen Installationen.
- Migrationen gegen die produktive Datenbankversion.
- Verhalten der Security-Firewalls und Request-Scopes in der Zielinstallation.
- Deprecations und API-Brüche, die nur während Container-Build oder Functional Tests sichtbar werden.

## Release-Gates

- `composer update --prefer-lowest` und normale Auflösung für beide Contao-Linien erfolgreich.
- PHPUnit und PHPStan grün.
- `debug:router`, `debug:container` und `contao:migrate` erfolgreich.
- Frontend- und Backend-Smoke-Tests in beiden Linien erfolgreich.
- Keine unbehandelten Deprecations im Contao-5.7-Lauf.
