# Sicherheitsleitlinien

- Anhänge werden außerhalb des Webroots gespeichert und nur über den Download-Controller ausgeliefert.
- Interne Notizen dürfen ausschließlich über Backend-Use-Cases erzeugt werden.
- Vor Produktivbetrieb ist ein Security Review einschließlich IDOR-, CSRF-, XSS- und Uploadtests durchzuführen.
- Der enthaltene lokale Storage setzt keinen Malware-Scanner um. Für produktive Umgebungen mit externen Uploads ist ein Scanner-Adapter zu ergänzen oder `scan_status` vor Freigabe auszuwerten.
- Gastzugriff ist im Datenmodell vorbereitet, aber in den gelieferten Frontend-Controllern bewusst nicht aktiviert.
