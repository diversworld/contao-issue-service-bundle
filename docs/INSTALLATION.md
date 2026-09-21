# Installation und Erstkonfiguration

1. Paket per Composer installieren.
2. `contao:migrate` ausführen.
3. Cache leeren.
4. Im Backend Pflichtkonfiguration prüfen.
5. Mindestens einen Service, eine Kategorie und Übergangsregeln anlegen.
6. Backend-Gruppen Servicezuordnungen geben.
7. Mailtransport und Absender in der Anwendung konfigurieren.
8. Schreibrechte auf `var/issue-attachments` sicherstellen.
9. Commands `issue-service:notifications:dispatch` und `issue-service:retention` über den betrieblichen Scheduler ausführen.
10. Negativtests mit fremdem Mitglied und fremder Backend-Gruppe durchführen.
