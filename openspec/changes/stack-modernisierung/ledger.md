# Planungsstand

Revision: r2 · Status: Plan vollständig; keine Umsetzungsfreigabe.

## Arbeitsort

Worktree wt-stack-modernisierung, Branch feature/stack-modernisierung, abgezweigt von master 579b863c3f74bc1c87e95be859a44cd0cddca5be

Absoluter Worktree: /home/rj/Developer/github.com/robjuz/wt-stack-modernisierung

## Route und Risiko

- Aufwandsklasse Standard; kanonisches Prüfprofil 0P1A; aktuelle Beauftragung ausschließlich Planung.
- Ein bestätigtes Vorhaben `stack-modernisierung`, vier Phasen, keine implizit zusätzlichen Changes.
- Aktuelles Budget gesamt/verbleibend: 0P / 0A / 0I; keine Reviews beauftragt oder verbraucht. Implementierer: 0. Eine spätere Umsetzung benötigt ihre eigene ausdrücklich bestätigte Beauftragung und dann den dafür geltenden Prüfrahmen.
- Risk: high — spätere Umsetzung betrifft Berechtigungen, Anmeldung, persistente Identitäten, Bestellungen und Buchungen. Das verändert nicht die gewählte Aufwandsklasse.
- Recording: inaktiv; keine Aktivierung in Projektregeln oder Schema gefunden.

## Entscheidungsvorgaben

| ID | Frage | Entscheidung | Quelle | Datum | Status |
| --- | --- | --- | --- | --- | --- |
| E1 | Liefergrenze | „mache zum Bereiche und Berechtigungen trennen → Profil samt Passwortverwaltung ergänzen → Administration aufbauen → Bestelloberfläche modernisieren. einen plan“ | Nutzer, wörtlich | 2026-11-12 | gültig |
| E2 | Profil und Administration | „ich müchte auch dem user eine profileansicht anbieten wo z.b. das password geändert werden kann. auserdem möchte ich einen ordentliche admin oberfläche haben. bis jetzt sind admin abläufe mit normalen abläufen vermischt.“ | Nutzer, wörtlich | 2026-11-12 | gültig |

| E3 | Anmeldepolitik F1 | „1“ = bestehende Anmeldewege erhalten; lokales Passwort nach sicherer Bestätigung ermöglichen | Nutzerantwort im Chat | 2026-11-12 | gültig |

## Entscheidung F1

Nutzerantwort wörtlich: „1“. Gewählt wurde: „Bestehende Anmeldewege erhalten: Nutzer dürfen nach sicherer Bestätigung ihrer Kontoinhaberschaft zusätzlich ein lokales Passwort setzen.“ E3 ist gültig; die Antwort genehmigt nur diese Anmeldepolitik, keine Implementierung und keinen Live-Rollout. Es gibt keine offene grundlegende Produktfrage.

## Annahmen und Nachweise

| Annahme | Risiko bei Irrtum | Nachweis | Ergebnis | Planfolge |
| --- | --- | --- | --- | --- |
| Filament/Inertia passen zum Backend | Stackentscheidung scheitert | isolierter Composer-Dry-Run, Metadaten | auflösbar, kein Laufzeitnachweis | tatsächliche Installation/Build in Paket 2/3 |
| Bestands-Mailboxen sind erreichbar | Konten können Übergang nicht abschließen | vor produktivem Rollout mit verantwortlicher Person prüfen | unbekannt; kein Produktionszugriff | Rollout erst bei bestätigtem Wiederherstellungsweg |
| Anbieter unterstützen stabile Subject-IDs | Identitätsbindung scheitert | vorhandenes Socialite-ID-Verhalten; Testcallbacks | lokal belegbar, Live-Konfiguration unbewiesen | Callbackprüfung mit Testkonten vor Rollout |
| Zwei Asset-Einstiege bleiben isoliert | Vue/Livewire-/CSS-Konflikte | integrierter Browser-/Buildtest | unproven | Paket 3 und 5 prüfen, keine zusätzlichen Frameworks |

## Akzeptanzmatrix

Produktanforderungen stehen ausschließlich in den Delta-Spezifikationen; folgende Zeilen referenzieren sie. Alle Implementierungsnachweise sind noch offen.

| Szenarioreferenz | Nachweis | Paket | Status | Evidenz |
| --- | --- | --- | --- | --- |


| area-access / Servergrenze / Direkte Zugriffe | PHPUnit HTTP | 1 | unproven | keine Implementierung |
| area-access / Servergrenze / Administrator | PHPUnit HTTP | 1 | unproven | keine Implementierung |
| area-access / Self-Service / Eigene Bestellung | PHPUnit HTTP | 1 | unproven | keine Implementierung |
| area-access / Self-Service / Manipulation und Fristen | PHPUnit HTTP | 1 | unproven | keine Implementierung |
| area-access / Self-Service / Adminsonderaktion | PHPUnit HTTP | 1 | unproven | keine Implementierung |
| area-access / Bereichswechsel / Navigation und Links | PHPUnit HTTP | 1 | unproven | keine Implementierung |
| area-access / Bereichswechsel / Abmelden | PHPUnit HTTP | 1 | unproven | keine Implementierung |
| profile-security / Eigenes Profil / Daten und Einstellungen | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Eigenes Profil / Feldgrenzen | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Passwortänderung / Erfolgreiche Änderung | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Passwortänderung / Ungültige Änderung | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Anmeldemethoden / Zusätzliches Passwort | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Anmeldemethoden / Externe Zuordnung | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Anmeldemethoden / Ungültiger Nachweis | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Bestandsübergang / Lokaler Bestand | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Bestandsübergang / Externer Bestand | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| profile-security / Bestandsübergang / Sperre und fehlende Bestätigung | PHPUnit Auth/Browser | 2 | unproven | keine Implementierung |
| finance-admin / Verwaltung / Benutzer und Angebote | PHPUnit Panel/Browser | 3/4 | unproven | keine Implementierung |
| finance-admin / Verwaltung / Bestellungen und Finanzen | PHPUnit Panel/Browser | 3/4 | unproven | keine Implementierung |
| finance-admin / Gemeinsame Fachlogik / Gleiche Ergebnisse | PHPUnit Panel/Browser | 3/4 | unproven | keine Implementierung |
| finance-admin / Gemeinsame Fachlogik / Wiederholung | PHPUnit Panel/Browser | 3/4 | unproven | keine Implementierung |
| finance-admin / Änderungsprotokoll / Nachvollziehbarkeit | PHPUnit Panel/Browser | 3/4 | unproven | keine Implementierung |
| finance-admin / Änderungsprotokoll / Fehlschlag | PHPUnit Panel/Browser | 3/4 | unproven | keine Implementierung |
| ordering-experience / Bestellablauf / Navigation und Auswahl | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |
| ordering-experience / Bestellablauf / Übersicht und Storno | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |
| ordering-experience / Robuste Rückmeldung / Fehler und Wiederholung | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |
| ordering-experience / Robuste Rückmeldung / Zugängliche Bedienung | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |
| ordering-experience / Auslieferung / Build und Update | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |
| ordering-experience / Auslieferung / Offline und Isolation | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |
| ordering-experience / Bestandskompatibilität / Migration und Kalender | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |
| ordering-experience / Bestandskompatibilität / Abgesicherte Rücknahme | Playwright/Build/Fixtures/Tastatur | 5 | unproven | keine Implementierung |

## Recherche und Belege

- `composer show --direct`: Laravel 13.42.0, Socialite 5.32.0, PHP CLI 8.5.12.
- `php artisan route:list --except-vendor --no-interaction`: 54 Routen; Verwaltungs- und Nutzerrouten teilen bisher auth:web; routes/api.php ist im aktuellen Bootstrap nicht als API-Routing registriert. Vor einer Migration reale Routen prüfen; nicht aus einer vorhandenen Datei auf aktive Endpunkte schließen.
- Repositoryquellen geprüft: routes/web.php, bootstrap/app.php, AppServiceProvider/AuthServiceProvider, User/Meal, UserPolicy/OrderItemPolicy, OrderItem/Order/Deposits/DepositTransfer/AutoOrder/Login/Settings/UserController, Requests, Views und vorhandene Featuretests.
- Read-only Recherchedelegation zu Auth/Rechten: keine P/A-Prüfung; keine Schreibrechte oder mutierenden Dienste beauftragt. Native Werkzeuge nicht hostseitig beschränkt, No-write-Anweisung als Vertrauensgrenze.
- Konkrete Befunde: OrderItemController::update ohne Autorisierung; OrderController::edit ohne explizite Autorisierung; Deposits-Leseansichten und DepositTransfer/AutoOrder ohne ausreichende Bereichsgrenze; globaler Adminfallback; Profilupdate aktuell admin-only und erlaubt is_admin; OAuth-Passwort von externer ID abgeleitet, Identität nur über E-Mail zugeordnet.
- Öffentliche Packagist-/npm-Metadaten am 2026-11-12 gelesen: Filament 5.11.2/support mit Laravel-13-Support und Livewire ^4.4.8; Livewire 4.4.8; inertia-laravel 3.7.0; Vue 3.5.45; @inertiajs/vue3 3.11.0; Vite 8.3.8; laravel-vite-plugin 3.2.1; @tailwindcss/vite 4.3.5; shadcn-vue 2.9.0. Keine Behauptung identischer Client-/Server-Patchversionen erforderlich; Integration noch unbewiesen.
- Isolierter Spike `/tmp/futtertrog-stack-spike-y4alui4_`: Kopie der Composer-Vorgaben, Filament ^5.11 und inertia-laravel ^3.7 ergänzt; update --dry-run --no-install --no-scripts --no-plugins erfolgreich, 183 auflösbare Pakete, keine Installation oder Änderung der Projekt-Lockdatei. Keine Laufzeitvalidierung.
- OpenSpec-Preflight 1.14.3 erfolgreich; Initialisierung ausschließlich im Planungsworktree.
- Kein vorhandenes openspec/specs, keine eindeutig zugeordnete OKF-Basis und kein okf-CLI gefunden; keine Wissensbasis angelegt.
- Strukturgraph fehlt. Installiertes graphifyy 0.9.55 weicht vom Skill-Pin 0.9.53 ab; daher Quellen direkt gelesen, kein falscher Graphnachweis und keine Installation. Setup-Reparatur bei Bedarf über hw-skills doctor; nicht Teil dieses Auftrags.

## Grenzen

Nur Planungsdokumente und OpenSpec-Konfiguration dürfen geändert werden. Keine Produktdateien, Abhängigkeiten, Datenbank, externen Dienste, Produktion, Pushes oder Deployments. E3 ist verbindlich; bisherige Loginmöglichkeiten erhalten, ungeklärte Credentials vor Bestätigung nicht als gesichert behandeln. Neue Anforderungen und produktive Migration separat freigeben.

## Run Ledger

| Schritt | Ergebnis | Rest |
| --- | --- | --- |
| Sitzungsstart | projekt-init lieferte Orte; keine Migration ausgeführt | keine |
| Basis | origin abgerufen; master und origin/master auf 579b863 | keine |
| Worktree | Helfer legte Featureworktree an, brach vor Abhängigkeitsverkettung ab: node_modules fehlt im Hauptbaum | Für Dokumentplanung keine Installation nötig; kein vollständiger Bootstrap/Baseline behauptet |
| Ownership | worktree-besitz anspruch erfolgreich | vor Delegation erneut prüfen |
| Paketspike | Abhängigkeiten im temporären Dry-Run auflösbar | Runtime-/Frontendbuild erst im späteren Umsetzungspaket |
| Entwurf r1 | Vierphasen-Roadmap und offene Authentscheidung dokumentiert | abgeschlossen durch E3 |
| Entscheidung | Nutzer wählt Option 1; als E3 erfasst | keine grundlegende Produktfrage offen |
| Detailplan r2 | Design, fünf Arbeitspakete innerhalb vier Phasen und vier Delta-Spezifikationen erstellt | Inhaltscommit nach strikter Validierung |

## Cleanup

Hauptcheckout unverändert; dessen vorhandene Handoffdateien unberührt. Planungsworktree bleibt erhalten. Temporärer Composer-Spike enthält nur kopierte Vorgaben, keine installierten Pakete. Keine Anwendungstests ausgeführt, da keine Implementierung vorliegt. Keine formale Planfreigabe und keine Umsetzungsfreigabe vorhanden.

## Integration und Ausführung

Ein Integrationsverantwortlicher führt Pakete 1 → 2 → 3 → 4 → 5 seriell zusammen. Paket 4 darf nach Resource-Dateien parallel aufgeteilt werden; ein Schreiber je Worktree. Pfadüberschneidungen bei Routen, Policies, app, Views, Tests und Rootdateien sind erwartet, kein Beweis unabhängiger Ausführung. Grenzen und alleinige Eigentümer von Lockfiles/Migrationen stehen ausschließlich in tasks.md. Mechanische Pfadprüfung mit disjunkt.sh: erwarteter Exit 1, gemeinsame app/routes/resources/tests und Rootdateien. Konfliktverantwortung und serielle Reihenfolge sind oben festgelegt; kein paralleler Zugriff auf gemeinsame Dateien.

Aktuelle Autorität: nur Planung. Ein späterer Implementierungsauftrag benötigt explizite Freigabe von r2 und des Ausführungsprofils. Planungsbudget bleibt 0P/0A/0I, keine unabhängigen Reviews durchgeführt. Geplanter Standard-Ausführungsrahmen wäre ein Change mit 0P/1A/0I; er ist durch die Antwort „1“ nicht aktiviert. Kein Push, Merge oder Deployment ohne Auftrag.

Prüfung der Planungsartefakte: OpenSpec strict und Umfangskontrolle vor Inhaltscommit; Produktabnahme bleibt unproven. Git-Commit umfasst ausschließlich OpenSpec-Konfiguration und diesen Change. Handoff verweist auf diese unfreigegebene Revision.

## Planvalidierung

OpenSpec strict für r2: erfolgreich, keine Warnungen. Produktnachweise weiterhin unproven; keine Implementierung ausgeführt.
