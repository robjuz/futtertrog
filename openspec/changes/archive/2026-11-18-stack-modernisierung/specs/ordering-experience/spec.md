# ordering-experience

## Purpose

Revision: r2

Die Bestelloberfläche unterstützt mobile und zugängliche Abläufe mit zuverlässigem Zustand und reproduzierbarer Auslieferung.

## ADDED Requirements

### Requirement: Bestellablauf
Die Anwendung SHALL Tagesauswahl, Anbieterfilter, Varianten, Mengen und eigene Bestellungen in einer zusammenhängenden mobilen Oberfläche anbieten.

#### Scenario: Navigation und Auswahl
- **GIVEN** ein Nutzer wählt Tag und Anbieter und betrachtet ein Gericht mit Varianten
- **WHEN** er bestellt und navigiert anschließend zurück
- **THEN** bleiben Datum und Filter über die URL nachvollziehbar, Menge und Variante stimmen und die Bestellung erscheint nach Serverbestätigung

#### Scenario: Übersicht und Storno
- **GIVEN** eigene aktuelle und künftige Bestellungen sowie Guthaben und Verlauf sind vorhanden
- **WHEN** der Nutzer die Übersicht öffnet und zulässig storniert
- **THEN** sieht er seine Daten und den bestätigten aktualisierten Bestellzustand ohne Verwaltungsfelder

### Requirement: Robuste Rückmeldung
Die Anwendung SHALL laufende, fehlgeschlagene und bestätigte Aktionen für den Nutzer eindeutig unterscheiden.

#### Scenario: Fehler und Wiederholung
- **GIVEN** eine Bestellung trifft auf einen Netz-, Validierungs-, Frist- oder Sitzungsfehler
- **WHEN** die Antwort ausbleibt oder zurückgewiesen wird
- **THEN** erscheint kein falscher Erfolg, die Auswahl bleibt erhalten und Wiederholung beziehungsweise erneute Anmeldung ist verständlich möglich

#### Scenario: Zugängliche Bedienung
- **GIVEN** die Oberfläche wird auf Smartphone oder Desktop mit Tastatur beziehungsweise unterstützender Technik bedient
- **WHEN** der Nutzer Filter, Varianten und Formulare nutzt
- **THEN** sind Fokus, Beschriftungen, Statusansagen und Kontrast nutzbar; reduzierte Bewegung wird respektiert

### Requirement: Auslieferung
Die Anwendung SHALL versionierte Oberflächenassets mit nachvollziehbarem PWA-Update und ehrlichem Offlinezustand ausliefern.

#### Scenario: Build und Update
- **GIVEN** ein sauberer Checkout wird in CI und Docker gebaut und eine neue Version veröffentlicht
- **WHEN** ein Browser mit altem Service Worker aktualisiert
- **THEN** stehen die gebauten Assets konsistent bereit und ein Update führt nicht zu dauerhaft veralteten JS-/CSS-Dateien

#### Scenario: Offline und Isolation
- **GIVEN** ein angemeldeter Browser verliert die Verbindung oder wechselt zwischen Nutzer- und Adminbereich
- **WHEN** er eine Aktion ausführt beziehungsweise eine Ansicht öffnet
- **THEN** wird offline keine erfolgreiche Bestellung behauptet, personalisierte Antworten werden nicht im Service-Worker-Cache abgelegt und Assets der Bereiche kollidieren nicht

### Requirement: Bestandskompatibilität
Die Anwendung SHALL bestehende fachliche Daten und tatsächlich genutzte Integrationen bei Migration und Oberflächenwechsel erhalten.

#### Scenario: Migration und Kalender
- **GIVEN** ein Bestandsfixture enthält Benutzer, Einstellungen, Gerichte, Bestellungen und Buchungen
- **WHEN** additive Migration und Oberflächenwechsel durchgeführt werden
- **THEN** bleiben fachliche Werte erhalten, Kalenderzugang und vorhandene Scheduler-/Anbieterfunktionen funktionieren und Bestellstatusabfragen werden ohne pro-Karte wiederholte Queries geladen

#### Scenario: Abgesicherte Rücknahme
- **GIVEN** neue Identitäten und Bestellungen sind nach der Umstellung entstanden
- **WHEN** die Nutzeroberfläche auf die zuvor abgesicherte Variante zurückgestellt wird
- **THEN** bleiben neue Daten und die Credential-/Berechtigungsgrenzen erhalten; unsicherer Altcode wird nicht reaktiviert
