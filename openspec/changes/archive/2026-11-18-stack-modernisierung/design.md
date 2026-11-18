# Architektur und Migration

Revision: r2

## Context

`routes/web.php` mischt Verwaltung und Nutzerbereich unter `auth:web`. `UserUpdateRequest` ist admin-only und erlaubt Rollenfelder. `OrderItemController::update` autorisiert das Objekt nicht ausdrücklich; weitere Verwaltungsaktionen sind nur durch Navigation versteckt. Ein globaler Adminfallback erschwert kontextbezogene Grenzen. `Meal::orderItem()` fragt trotz vorgeladener Beziehungen wiederholt die Datenbank ab. Das manuelle Bestell-JavaScript behandelt Netz-/HTTP-Fehler nicht.

OAuth findet bisher Konten über E-Mail und setzt bei Neuanlage einen Hash der externen ID als Passwort. Anbieter und stabile externe Identität fehlen im Datenmodell. Ein vorhandener Hash beweist somit keine eingerichtete lokale Anmeldemethode. E3 entscheidet für mehrere verknüpfte Anmeldemethoden mit bestätigter Kontoinhaberschaft.

## Architecture

Drei sichtbare Bereiche: Bestellen und Übersicht, persönliches Profil, Administration. E1/E2 verlangen getrennte Abläufe, keine getrennten Datenbanken oder Benutzerbestände.

| Bestandteil | Wiederverwendung / Änderung | Begründung |
| --- | --- | --- |
| User, Meals, Orders, Deposits, Money | Modelle und fachliche Berechnung erhalten | Bestandskompatibilität und finance-admin / Verwaltung |
| Policies und AuthServiceProvider | explizite Nutzer-/Adminfähigkeiten statt globaler Freigabe | area-access / Servergrenze |
| Self-Service-Controller und Requests | nur eigene Ressourcen; separate Profil-/Passwort-Requests | area-access / Self-Service, profile-security / Eigenes Profil |
| Admincontroller, später Filament-Resources | eigene serverseitige Bereichsgrenze; nach Übernahme alte Schreibwege entfernen | area-access / Servergrenze, finance-admin / Verwaltung |
| Fachliche Actions | nur mehrfach benötigte Bestell-/Buchungsübergänge extrahieren | finance-admin / Gemeinsame Fachlogik; vorhandene Controller sind keine neutrale Schnittstelle |
| ExternalIdentity und CredentialChallenge | neue persistente Zuordnung und einmalige Bestätigung | E3, profile-security / Anmeldemethoden und Bestandsübergang |
| AdminActivity | protokolliert gezielte administrative Änderungen | finance-admin / Änderungsprotokoll; Events allein sind kein dauerhaftes Protokoll |
| Vue-Seiten und gemeinsames App-Layout | Profil zuerst, Bestellansichten danach | E1/E2, ordering-experience |
| Filament-Panel | getrenntes Layout und eigener Einstieg | E2, finance-admin / Verwaltung |
| Vite/CI/Docker | reproduzierbare versionierte Assets | ordering-experience / Auslieferung |

### Routen und Berechtigungen

Nutzerpfade: `/`, `/meals`, `/my/orders`, `/my/balance`, `/profile`, `/profile/security`, `/profile/preferences`. Adminpfade ausschließlich unter `/admin`; Kalenderpfad bleibt kompatibel. Keine neue öffentliche Registrierung. GET-Abmeldung entfällt zugunsten POST mit CSRF.

Self-Service nutzt immer die Sitzungsidentität. Benutzer-IDs für Stellvertretung und Rollenfelder sind dort unzulässig. Eigene Bestelländerungen prüfen Eigentum und bestehende Fristen auch für Administratoren. Admin-Stellvertretung und zulässige Fristsonderaktionen haben eigene autorisierte Endpunkte; bisherige Befugnisse bleiben dort explizit erhalten. Alle Lese-, Schreib-, Import-, Übertrags- und Autoorder-Aktionen erhalten Serverprüfungen, einschließlich alter Routen.

Routen-Grenze plus objektbezogene Policies, kein Verlass auf ausgeblendete Links. `is_admin` bleibt die einzige Rolle. Der globale Gate-Fallback und kontextfreie Admin-Bypässe entfallen. Bestehende Regel gegen Selbstlöschung erhalten; Rollenänderungen dürfen den letzten aktiven Admin nicht entfernen. Profilupdates können weder Rollen noch fremde Daten ändern.

## Profile and authentication

Profilbereiche Konto, Sicherheit, Einstellungen. Name editierbar; E-Mail sichtbar, zunächst unveränderbar. Bestehende Sprache, Präferenzen, Abneigungen, Benachrichtigungen und Darstellungsoptionen übernehmen. Sensible Antworten enthalten niemals Passwort-Hashes, Tokens oder komplette Modelserialisierungen.

### Datenmodell

Additive Migrationen, bestehende Basismigrationen nicht umschreiben:

- `external_identities`: Benutzer, erlaubter Anbieter und dessen stabile Subject-ID, eindeutiges Paar Anbieter/Subject. Anbieter aus Konfigurations-Whitelist. Keine OAuth-Access-/Refresh-Tokens speichern, solange bestehende Funktionen sie nicht benötigen.
- Benutzer erhält expliziten lokalen Credential-Status `legacy`, `enabled` oder `disabled`. Alle Bestandskonten beginnen als `legacy`; Passwort-Hash bleibt erhalten, keine automatische Klassifikation aus dem Hash. Neue externe Konten: `disabled` und kryptografisch zufälliger, unbekannter Platzhalterhash; neue explizite lokale Konten: `enabled`.
- `credential_challenges`: Benutzer, Zweck, gehashter Zufallswert, Ablauf, Verbrauchszeit und gegebenenfalls ausstehende externe Identität. Maximal 15 Minuten gültig, einmalig, zweck- und kontogebunden; keine Passwörter im Datensatz. Versandraten begrenzen.
- `admin_activities`: Akteur, Zeitpunkt, Aktion, Ziel und erlaubte Änderungsfelder; keine Geheimnisse oder vollständigen Personenmodelle.

### Bestandsübergang gemäß E3

1. Bei bereits verknüpfter externer Identität: Anmeldung ausschließlich über Anbieter/Subject; gleiche E-Mail allein ist kein Nachweis. Geänderte Provider-E-Mail ändert nicht die Zuordnung.
2. Beim ersten erneuten OAuth-Login eines Bestandskontos: OAuth-State prüfen, Identität zunächst ausstehend halten; Link zur Kontobestätigung an die bisher gespeicherte E-Mail senden. Erst danach bestehendes Konto zuordnen und Anmeldung abschließen. Bei bereits authentifiziertem Konto ebenfalls frische Bestätigung verlangen; Verknüpfungskollisionen abbrechen.
3. Bei noch ungeklärter lokaler Bestandsanmeldung: vorhandenes Passwort prüfen, danach einmalige E-Mail-Bestätigung vor vollständiger Sitzung. Bei erfolgreicher Bestätigung explizit ein lokales Passwort setzen lassen und Status `enabled` setzen. Die Passwort-Anmeldemöglichkeit bleibt erhalten; ungeklärte Altpasswörter gelten nicht als bereits bestätigte lokale Zugangsdaten.
4. Bestehende Sitzungen dürfen bis zum üblichen Ablauf normale Aufgaben weiterführen; credentialbezogene Aktionen erfordern frische Bestätigung. Bestandskonten werden nie massenhaft gelöscht, zusammengeführt oder mit unbekannten neuen Passwörtern überschrieben. Deaktivierte Konten bleiben gesperrt.
5. Ohne Zugriff auf gespeicherte E-Mail kein automatischer Ersatznachweis und keine automatische Verknüpfung. Bestehenden Support-/Wiederherstellungsprozess nutzen; ein neuer Identitätsprüfungsprozess ist nicht Teil dieses Vorhabens. Die Erreichbarkeit der Bestands-Mailboxen ist eine vor Rollout zu prüfende Annahme.

Neue externe Identität ohne bestehenden E-Mail-Konflikt kann ein Konto ohne lokale Methode anlegen. Bei E-Mail-Konflikt stets bestätigter Übergang, keine stille Zusammenführung. Neue externe Konten erhalten keine Anmeldung per bekannter Anbieter-ID als Passwort.

### Passwortoperationen

Bei `enabled`: aktuelles Passwort, neues Passwort, Bestätigung; mindestens 12 Zeichen, Laravel-Hashing und zentral identische Regeln in Ändern/Setzen/Reset. Falsche Angaben ändern nichts. Nach Erfolg Sitzung regenerieren und andere Sitzungen/Remember-Tokens über die passende Laravel-Konfiguration invalidieren; aktuelle Sitzung bleibt nutzbar. Keine externen Passwortprüfdienste erforderlich.

Bei `disabled` oder `legacy`: lokales Passwort erst nach der einmaligen E-Mail-Bestätigung einrichten. Dasselbe Verfahren bestätigt eine neue externe Verknüpfung; ein Token gilt nie für einen anderen Zweck. Öffentlicher Passwortreset verwendet dieselbe bestätigte Einrichtung, ohne lokale Credentials unkontrolliert zu aktivieren. Generische Antworten verhindern Kontenaufzählung; keine Passwörter per Mail. Passwortänderung entfernt keine externe Verknüpfung. Eine UI zum Entfernen/Wechseln von Anmeldemethoden ist nicht vorgesehen.

## Administration

Phase 1 verschiebt notwendige bestehende Verwaltungsansichten hinter die Admin-Grenze. Phase 3 ersetzt sie schrittweise mit Filament 5; Self-Service bleibt Vue/Blade bis Phase 4.

Panel: Benutzer suchen/filtern/deaktivieren/wiederherstellen und Rechte pflegen; Gerichte/Varianten und bestehende Importe; Sammelbestellungen nach Datum/Anbieter/Status, Bearbeitung/Abschluss/Autoorder; Einzahlungen/Überträge; bestehende Mitteilungen. Dashboard zeigt vorhandene fachliche Zustände, keine neu erfundene Importüberwachung. Passwörter über Wiederherstellung statt administrativ eingegebener Klartextwerte verwalten.

Geldberechnung, Transaktionen, Anbieteraufrufe und bestehende Statusübergänge zentral wiederverwenden. Finanz- und externe Bestellaktionen gegen Doppelausführung absichern; keine blinde Wiederholung bei unklarem Anbieterergebnis. Protokoll schreibt nur freigegebene administrative Felder und teilt bei Datenbankänderungen die fachliche Transaktion. Kein allgemeines Event-Sourcing oder revisionssicheres Buchhaltungssystem.

## Ordering experience

Mobile Tages-/Wochenauswahl, Anbieterfilter, Varianten und Menge, Bestellen/Storno. Datum und Filter in URL, Browser-Zurück und Scrollposition erhalten. Auswahl bleibt bei Fehlern; Loading pro betroffener Aktion; sichtbarer Erfolg erst nach Serverbestätigung. Abgelaufene Sitzung führt verständlich zur Anmeldung, keine Erfolgsmeldung bei Fehlerantworten.

Dashboard, eigene Bestellungen, Guthaben und Verläufe übernehmen; keine administrativen Bearbeitungsfelder im Self-Service. Vorgeladene Bestell-/Variantendaten statt wiederholter `orderItem()`-Queries verwenden. Query-Zahl anhand repräsentativer Fixtures prüfen, keine ungemessene Geschwindigkeitszusage.

Schrift, Abstände und Komponenten als kleines konsistentes Designsystem. Tastatur/Fokus, Formularbeschriftungen, Statusansagen und Kontrast prüfen; reduzierte Bewegung respektieren. Desktop und Smartphone mit Playwright prüfen, gezielte manuelle Tastaturkontrolle ergänzen.

Vite trennt Nutzer- und Panel-Einstieg; kein Vue/Alpine-Doppelmount und kein globales CSS-Leck. Node 24 LTS als Buildwerkzeug, nicht als zusätzlicher Produktionsdienst. npm-Lockdatei, Buildstage und CI; finales Image enthält gebaute Assets. Bestehendes Auth-UI erst nach Funktionsparität entfernen. Kein SSR oder Pinia ohne konkreten Bedarf.

## Compatibility, rollout and rollback

Phase 1 → 2 → 3 → 4, jeweils Integration und betroffene Regressionstests vor dem nächsten Schritt. Alle neuen Migrationen und gemeinsamen Lockfiles haben einen Integrationsverantwortlichen. Unabhängige Adminmasken können nach Panel-/Actionbasis in getrennten Worktrees parallel entstehen.

Vor Rollout: wiederherstellbares Backup und Migration mit anonymisierten Fixtures, lokale und externe Testkonten sowie deaktiviertes Konto, Mailzustellung und Callback-Konfiguration überprüfen. Keine produktiven Änderungen durch diesen Plan. Kalender und tatsächlich registrierte Schnittstellen vor/nach Umstellung vergleichen; `routes/api.php` ist im aktuellen Bootstrap nicht registriert und wird nicht beiläufig aktiviert.

Alte GET-Verwaltungslinks dürfen zu autorisierten neuen Zielen umleiten; alte schreibende Wege nach Umschaltung schließen. Keine Freigabe von `/admin` über frei manipulierbare Frontendzustände. Frische Testdatenbank und additive Aktualisierung eines Bestandsfixtures müssen funktionieren.

Rollback: Nutzeroberfläche kann auf die zuletzt abgesicherte Blade-Version zurück, sofern neue Credential-/Policy-Grenzen erhalten bleiben. Nie auf den unsicheren alten Authcode oder ungeprüfte Verwaltungsrouten zurückrollen. Neue Identitäts-/Credentialtabellen nicht löschen; bei Authproblemen Setzen/Verknüpfen pausieren und vorwärts korrigieren, vorhandene bestätigte Loginmethoden erhalten. Jede Umschaltung bewahrt Bestellungen und Buchungsdaten.

## Decisions, risks and completion

E1 bestimmt Reihenfolge, E2 Profil und getrennte Administration, E3 bestätigte parallele Anmeldemethoden. Architekturentscheidungen oben sind Planervorgaben innerhalb dieses Rahmens, keine Nutzerzitate. Gegenentwurf Livewire auch für Nutzer hätte weniger Frontendtechniken; Vue/Inertia bleibt wegen des besprochenen interaktiven Zielbilds gewählt. Neubewertung erst bei nachgewiesenem Integrationsproblem, nicht automatisch zusätzliche Frameworks.

Paketauflösung ist bewiesen, echter Build/Betrieb nicht; Mailboxerreichbarkeit und reales Datenvolumen sind unbekannt. Kein Zugriff auf produktive Daten für die Planung. Sicherheitsbefunde aus der Recherche werden innerhalb Phase 1/2 behoben, daraus entsteht kein voller Auditauftrag. Fertigstellung der späteren Umsetzung bemisst sich ausschließlich an den Delta-Szenarien, Tests und menschlicher Bedienabnahme; aktuell sind sie unbewiesen.
