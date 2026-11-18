# area-access

## Purpose

Revision: r2

Nutzer- und Verwaltungsabläufe besitzen nachvollziehbare serverseitige Grenzen bei gemeinsamem Benutzerbestand.

## ADDED Requirements

### Requirement: Servergrenze
Die Anwendung SHALL alle administrativen Lese- und Schreibabläufe einschließlich bestehender Altrouten durch ausdrückliche administrative Berechtigungen schützen.

#### Scenario: Direkte Zugriffe
- **GIVEN** ein Gast oder normaler Nutzer kennt eine Admin- oder alte Verwaltungsadresse
- **WHEN** er eine Lese-, Änderungs-, Import-, Übertrags- oder Autoorder-Anfrage direkt sendet
- **THEN** werden keine fremden Verwaltungsdaten offengelegt und keine administrativen Wirkungen ausgelöst

#### Scenario: Administrator
- **GIVEN** ein aktiver Administrator ist angemeldet
- **WHEN** er in die Administration wechselt
- **THEN** erhält er eine eigene Navigation und die ausdrücklich erlaubten Verwaltungsaktionen; ein deaktiviertes Konto erhält keinen Zugang

### Requirement: Self-Service
Die Anwendung SHALL im normalen Nutzerbereich ausschließlich eigene Ressourcen innerhalb der normalen fachlichen Fristen bearbeiten.

#### Scenario: Eigene Bestellung
- **GIVEN** ein Nutzer oder Administrator verwendet den normalen Bestellbereich
- **WHEN** er seine eigene zulässige Bestellung erstellt oder ändert
- **THEN** wird die Sitzungsidentität verwendet und das fachlich gültige Ergebnis gespeichert

#### Scenario: Manipulation und Fristen
- **GIVEN** ein angemeldetes Konto sendet eine fremde Benutzer-/Bestell-ID, administrative Felder oder eine unzulässige Änderung nach Fristablauf
- **WHEN** die Anfrage direkt am Self-Service-Endpunkt eingeht
- **THEN** wird die Aktion ohne unerlaubte Änderung zurückgewiesen, auch bei einem Administrator

#### Scenario: Adminsonderaktion
- **GIVEN** ein Administrator benötigt eine bisher zulässige Stellvertretung oder Fristsonderaktion
- **WHEN** er die ausdrücklich vorgesehene Verwaltungsaktion aufruft
- **THEN** wird die Berechtigung dort geprüft und die Sonderaktion bleibt außerhalb des normalen Bestellablaufs

### Requirement: Bereichswechsel
Die Anwendung SHALL Nutzerbereich, Profil und Administration als getrennte Bedienkontexte darstellen.

#### Scenario: Navigation und Links
- **GIVEN** ein Administrator öffnet die normale Oberfläche oder einen bisherigen Verwaltungslink
- **WHEN** er navigiert
- **THEN** bleiben normale Ansichten frei von Verwaltungsfeldern und Bearbeitungslinks, ein ausdrücklicher Admin-Einstieg bleibt erreichbar und alte GET-Links führen nur zu berechtigten Zielen

#### Scenario: Abmelden
- **GIVEN** ein Konto besitzt eine aktive Sitzung
- **WHEN** eine GET-Anfrage beziehungsweise eine gültige POST-Abmeldung eingeht
- **THEN** ändert GET die Sitzung nicht und POST meldet mit CSRF-Schutz ab
