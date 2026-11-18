# finance-admin

## Purpose

Revision: r2

Eine eigenständige Administration bildet bestehende Fachabläufe über gemeinsame Regeln und nachvollziehbare Änderungen ab.

## ADDED Requirements

### Requirement: Verwaltung
Die Anwendung SHALL administrative Benutzer-, Angebots-, Bestell-, Finanz- und Mitteilungsabläufe in einer eigenständigen Oberfläche anbieten.

#### Scenario: Benutzer und Angebote
- **GIVEN** ein Administrator arbeitet im Panel
- **WHEN** er Benutzer oder Gerichte einschließlich Varianten und bestehender Importe verwaltet
- **THEN** stehen Suche, Filter, Pagination und die bisherigen erlaubten Aktionen zur Verfügung; Selbstlöschung und Entfernung des letzten aktiven Admins werden verhindert

#### Scenario: Bestellungen und Finanzen
- **GIVEN** ein Administrator bearbeitet Sammelbestellungen, Einzahlungen, Überträge oder Mitteilungen
- **WHEN** er eine zulässige Aktion bestätigt
- **THEN** wird der bisherige fachliche Vorgang ausgeführt und Erfolg oder Fehler verständlich angezeigt

### Requirement: Gemeinsame Fachlogik
Die Anwendung SHALL fachliche Bestell- und Geldübergänge in Administration und Nutzerbereich mit gemeinsamen Regeln ausführen.

#### Scenario: Gleiche Ergebnisse
- **GIVEN** vergleichbare zulässige Vorgänge werden über alte abgesicherte Abläufe beziehungsweise neue Oberflächen ausgelöst
- **WHEN** sie abgeschlossen werden
- **THEN** stimmen Beträge, Mengen und Status fachlich überein und Anbieteraktionen laufen in Tests ausschließlich gegen Fakes

#### Scenario: Wiederholung
- **GIVEN** eine Buchung oder automatische Anbieterbestellung läuft bereits oder ihr Ergebnis ist unklar
- **WHEN** dieselbe Aktion erneut angefordert wird
- **THEN** entsteht keine unkontrollierte doppelte Buchung oder Anbieterbestellung; der Zustand wird nachvollziehbar behandelt

### Requirement: Änderungsprotokoll
Die Anwendung SHALL administrative Berechtigungs-, Bestell- und Finanzänderungen mit Akteur, Zeitpunkt, Ziel und relevanten erlaubten Änderungen protokollieren.

#### Scenario: Nachvollziehbarkeit
- **GIVEN** ein Administrator führt eine entsprechende Änderung durch
- **WHEN** die Änderung erfolgreich gespeichert wird
- **THEN** liegt ein zugehöriger Datensatz ohne Passwörter, Tokens oder andere Geheimnisse vor

#### Scenario: Fehlschlag
- **GIVEN** eine transaktionale Datenbankänderung scheitert
- **WHEN** die Transaktion zurückgerollt wird
- **THEN** behauptet das Protokoll keinen erfolgreich gespeicherten Vorgang
