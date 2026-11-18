# profile-security

## Purpose

Revision: r2

Persönliche Daten und bestätigte lokale oder externe Anmeldemethoden werden sicher und ohne Rollenvermischung verwaltet.

## ADDED Requirements

### Requirement: Eigenes Profil
Die Anwendung SHALL dem angemeldeten Nutzer ein eigenes Profil mit Name, angezeigter E-Mail und bisherigen persönlichen Einstellungen anbieten.

#### Scenario: Daten und Einstellungen
- **GIVEN** ein bestehendes Konto besitzt persönliche Einstellungen
- **WHEN** der Nutzer sein Profil öffnet und erlaubte Angaben speichert
- **THEN** kann er Namen, Sprache, Vorlieben und Benachrichtigungen verwalten; E-Mail bleibt sichtbar und unverändert, bisherige Einstellungen gehen nicht verloren

#### Scenario: Feldgrenzen
- **GIVEN** ein Nutzer übermittelt fremde IDs oder Rollenfelder am Profil-Endpunkt
- **WHEN** die Anfrage verarbeitet wird
- **THEN** werden weder fremde Konten noch administrative Berechtigungen verändert

### Requirement: Passwortänderung
Die Anwendung SHALL die Änderung eines bestätigten lokalen Passworts durch aktuelles Passwort, neues bestätigtes Passwort und sichere Sitzungsbehandlung absichern.

#### Scenario: Erfolgreiche Änderung
- **GIVEN** ein Konto besitzt eine bestätigte lokale Anmeldemethode
- **WHEN** es sein korrektes aktuelles und ein gültiges bestätigtes neues Passwort angibt
- **THEN** gilt das neue Passwort mit mindestens 12 Zeichen, das alte gilt nicht mehr, die aktuelle Sitzung bleibt nutzbar und andere Sitzungen/Remember-Tokens verlieren ihren Zugang

#### Scenario: Ungültige Änderung
- **GIVEN** aktuelles Passwort, Mindestlänge oder Bestätigung sind ungültig
- **WHEN** der Nutzer speichert
- **THEN** erscheint eine verständliche Fehlermeldung ohne Credentialänderung und ohne Offenlegung von Hashes oder Tokens

### Requirement: Anmeldemethoden
Die Anwendung SHALL externe Identitäten und zusätzliche lokale Passwörter ausschließlich nach bestätigter Kontoinhaberschaft einem bestehenden Konto zuordnen.

#### Scenario: Zusätzliches Passwort
- **GIVEN** ein Konto meldet sich extern an und besitzt noch kein bestätigtes lokales Passwort
- **WHEN** der Nutzer den einmaligen kontogebundenen E-Mail-Nachweis abschließt und ein gültiges Passwort setzt
- **THEN** funktionieren danach lokale und bisherige externe Anmeldung für dasselbe Konto

#### Scenario: Externe Zuordnung
- **GIVEN** eine externe Identität ist gespeichert oder eine neue Identität trifft auf eine bestehende E-Mail
- **WHEN** ein gültiger OAuth-Callback eingeht
- **THEN** bestimmt die gespeicherte Anbieter-/Subject-Zuordnung das Konto; eine neue Verknüpfung benötigt bestätigte Inhaberschaft und eine Kollision wird ohne Zusammenführung abgewiesen

#### Scenario: Ungültiger Nachweis
- **GIVEN** ein Nachweis ist abgelaufen, wiederverwendet, für ein anderes Konto oder einen anderen Zweck ausgestellt
- **WHEN** er für Setzen, Reset oder Verknüpfung benutzt wird
- **THEN** entsteht keine Credential- oder Identitätsänderung; öffentliche Antworten verraten keine Kontenexistenz

### Requirement: Bestandsübergang
Die Anwendung SHALL ungeklärte Bestandskonten ohne pauschale Klassifikation in bestätigte Anmeldemethoden überführen.

#### Scenario: Lokaler Bestand
- **GIVEN** ein Bestandskonto besitzt noch Status legacy
- **WHEN** es sich mit dem bisherigen Passwort anmeldet und den E-Mail-Nachweis sowie die explizite Passwortsetzung abschließt
- **THEN** bleibt Passwortanmeldung möglich und das Konto erhält erst danach den bestätigten lokalen Status

#### Scenario: Externer Bestand
- **GIVEN** ein bisher extern verwendetes Konto hat noch keine gespeicherte externe Identität
- **WHEN** es sich erneut über den Anbieter und die bestätigte gespeicherte Mailbox legitimiert
- **THEN** wird die Identität demselben Konto zugeordnet, ohne die Anbieter-ID als lokales Passwort zu verwenden oder andere bestätigte Methoden zu entfernen

#### Scenario: Sperre und fehlende Bestätigung
- **GIVEN** ein Konto ist deaktiviert oder ein notwendiger Nachweis kann nicht abgeschlossen werden
- **WHEN** eine Anmeldung oder Verknüpfung versucht wird
- **THEN** bleibt ein deaktiviertes Konto gesperrt und ohne Nachweis erfolgt keine neue Zuordnung oder lokale Einrichtung; bestehende Daten werden nicht gelöscht
