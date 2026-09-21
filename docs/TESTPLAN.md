# Automatisierter Testplan

## Im Paket implementiert

- WorkflowPolicyTest: erlaubte und verbotene Statuswechsel
- SettingsParserTest: Boolean-, Integer- und JSON-Konfiguration
- TicketPatternTest: Platzhalter, Sequenzformat und ungültige Tokens
- UploadPolicyTest: Endung, Größe und maximale Anzahl
- RetentionCalculatorTest: reproduzierbare Stichtagsberechnung
- OwnershipPolicyTest: Mitgliedseigentum und Gast-Token-Hash
- SqlSortWhitelistTest: Schutz dynamischer Sortierung
- BundleStructureTest: Pakettyp, Contao-Constraint und Manager-Plugin-Konflikt
- DcaShapeTest: DCA-Grundstruktur und Pflichtfeld

## Im Zielprojekt zusätzlich auszuführen

1. Migration auf leerer MariaDB/MySQL-Datenbank.
2. Upgrade-Migration mit repräsentativem Contao-5.7-Datenstand.
3. Functional Tests über Symfony WebTestCase für Login, CSRF, 403/404 und PRG.
4. Downloadtest für fremde Mitglieds-ID und falsches Gast-Token.
5. Backend-DCA-Test mit Admin, Agent mit Servicegruppe und Agent ohne Servicegruppe.
6. Mailer-Test mit InMemoryTransport einschließlich Retry und Idempotenz.
7. Filesystem-Test mit temporärem Verzeichnis und simuliertem Scannerstatus.
8. Browser-E2E: Erstellen, Rückfrage, Antwort, Lösen, Schließen und Wiederöffnen.
