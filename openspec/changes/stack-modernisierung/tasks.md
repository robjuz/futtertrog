# Umsetzungspakete

Revision: r2 · Alle Aufgaben offen; kein Implementierungsauftrag.

Die Pakete werden innerhalb eines Changes bearbeitet. Für alle gelten: eigener Worktree, keine echten Anbieterbestellungen/E-Mails/Geldbewegungen, isolierte Testdatenbank, Provider-/Mail-Fakes. Nur benannte Pfade ändern; fremde Pakete, Produktion, Secrets und nicht beauftragte Abhängigkeiten sind verboten. Vor Teständerungen Projektregeln und `testing-best-practices` lesen; fehlt der Skill, vor Testautorisierung den fehlenden Standard klären. Tests mit PHPUnit und bestehenden Factories. Neue Laravel-Dateien mit passenden make-Kommandos erstellen.

## 1. Bereichsgrenzen und Berechtigungen

Abhängigkeit: keine. Input: bestehende Routen/Policies und Recherchebefunde. Output: abgesicherte Nutzer-/Adminwege mit vorläufigen Blade-Adminansichten.
Eigentum: `routes/web.php`, `app/Http`, `app/Policies`, `app/Providers/AuthServiceProvider.php`, betroffene `resources/views`, `tests/Feature`, `tests/Unit`. Verboten: Auth-Credentialmigration, Filament, Lockfiles. Szenarien: alle in area-access. Tests: Auth/User/Order/MealOrdering/Deposit/Notifications sowie neue direkte Negativfälle.

- [x] 1.1 Tatsächlich registrierte Routen und bestehende Befugnisse inventarisieren; kontrollierte Ausgangstests dokumentieren.
- [x] 1.2 Adminroute/-layout und explizite Fähigkeiten einführen; fehlende Lese-/Schreibprüfungen und globale Adminfreigaben ersetzen.
- [x] 1.3 Self-Service auf Sitzungsidentität und normale Fristen begrenzen; Stellvertretung/Sonderrechte in Adminaktionen verlagern.
- [x] 1.4 Altrouten absichern, GET-Abmeldung entfernen, Navigationsmischung auflösen und direkte HTTP-Regressionsfälle ausführen.

## 2. Profil, Anmeldemethoden und Frontendbasis

Abhängigkeit: 1 integriert. Input: E3 und design.md / Profile and authentication. Output: Profil einschließlich getesteter Bestandsüberführung und gemeinsamer Vue-Basis.
Eigentum: `app/Models/User.php`, neue Identitäts-/Challengekomponenten in `app`, `app/Http/Controllers/Auth`, Profilcontroller/-requests, Auth-/Profilrouten, `config/auth.php`, `config/services.php`, `database/migrations`, Factories, neue `resources/js`/CSS-Einstiege, Profil-Blade-Root, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, Vite-/TypeScriptkonfiguration, `Dockerfile`, `.github/workflows`, Auth-/Profiltests. Gemeinsame Rootdateien/Migrationen ausschließlich Paket-2-Integrator. Verboten: Anbieterfachlogik, Adminresources und Bestellseiten-Redesign. Szenarien: alle profile-security; ordering-experience / Auslieferung und Bereichsgrenzen bei Navigation. Tests: Login/LoginWithGitlab/PasswordReset/Settings/User plus neue Identitäts-, Challenge- und Profilszenarien; Produktionsbuild.

- [x] 2.1 Additive Datenmodelle und Bestandsfixtures für legacy/enabled/disabled sowie externe Identitäten und einmalige Challenges erstellen.
- [x] 2.2 Authcallback, lokale Altanmeldung, bestätigte Verknüpfung und erstes Passwortsetzen gemäß E3 implementieren; Deaktivierung/Kollisionen/Tokenzweck testen.
- [x] 2.3 Profilrequests, Passwortänderung/-reset und Sitzungsinvalidierung ohne Rollenfelder integrieren.
- [x] 2.4 Vue/Inertia/Vite/Tailwind mit getrennten Einstiegen, Lockfiles, CI-/Docker-Build und Profilseiten einführen; bestehende Einstellungen übernehmen.
- [x] 2.5 Frische Installation und additive Migration mit Fixtures, zwei Browserkontexten und Mail-Fakes nachweisen.

## 3. Adminbasis und gemeinsame Fachaktionen

Abhängigkeit: 2 integriert. Input: abgesicherte Adminwege und vorhandene Fachlogik. Output: Filamentbasis, wiederverwendete Fachaktionen, Protokollierung.
Eigentum: Filament-Panelprovider, notwendige `app/Actions`, Policies, Adminrouten/-controller, `app/Models/AdminActivity.php`, neue Protokollmigration, Filament-Theme, Composer-/npm-Lockfiles und Rootkonfiguration, Adminaktionstests. Alle Rootdateien und Migrationen ausschließlich Paket-3-Integrator. Verboten: Profil-/Identitätsverhalten ändern, Bestell-UI. Szenarien: finance-admin / Gemeinsame Fachlogik, Änderungsprotokoll sowie area-access / Servergrenze. Tests: bestehende Order/Deposit/MealImport und fokussierte Action-/Panelzugangstests.

- [x] 3.1 Filament 5/Livewire 4 auflösen und Panel mit bestehender Sitzung und explizitem Zugang integrieren.
- [x] 3.2 Nur mehrfach benötigte Fachübergänge extrahieren; Autorisierung, Transaktionen und Doppelausführung prüfen.
- [x] 3.3 Administrative Änderungen mit ausgeschlossenen Geheimnissen protokollieren; Integrationsschnittstellen für Resources festhalten.

## 4. Verwaltungsoberflächen

Abhängigkeit: 3 integriert. Input: getestete Panel-/Actionbasis. Output: vollständige Adminabläufe, alte Verwaltungsansichten ersetzt.
Eigentum: neue `app/Filament/Resources`, zugehörige Panelpages, Resource- und Browsertests. Während Parallelisierung nach Resource-Dateien aufteilen: Benutzer; Gerichte/Importe; Bestellungen/Finanzen/Mitteilungen. Rootdateien, Migrationen und gemeinsame Actions bleiben dem Integrator vorbehalten. Verboten: fachliche Regeln in Resources duplizieren, direkte Anbieteraufrufe in Tests. Szenarien: alle finance-admin und Bereichsgrenze aus area-access. Tests: Panelzugang, vorhandene fachliche Suiten, Browser-Smoke der Verwaltungsabläufe.

- [x] 4.1 Benutzerpflege einschließlich Wiederherstellung und letztem Administrator sowie Angebote/Varianten/Importe anbinden.
- [x] 4.2 Sammelbestellungen, zulässige Sonderaktionen, Einzahlungen/Überträge und Mitteilungen anbinden; Ergebnis und Fehler anzeigen.
- [x] 4.3 Suche, Filter, Pagination und Bestätigungen fertigstellen; bisherige Verwaltungsseiten erst nach Funktionsparität abschalten.

## 5. Nutzeroberfläche und PWA

Abhängigkeit: 4 integriert; wahrt E1-Phasenreihenfolge. Input: vorhandene Fachaktionen, Profil-UI und getestete Admintrennung. Output: vollständige Vue-Nutzeroberfläche und zuverlässige Auslieferung.
Eigentum: Nutzerseiten/-komponenten unter `resources/js`, zugehörige Styles, `HomeController`/`MealController` und Self-Service-Datenaufbereitung, `Meal.php` für vorgeladene Bestelldaten, Nutzer-Blade-Root, PWA-Konfiguration und `public/serviceworker.js`, Nutzer-Browsertests. Lockfiles/Buildkonfiguration nur Integrator nach dokumentiertem Bedarf. Verboten: Adminresources, neue Fristen-/Geldregeln, Offlinebestellung. Szenarien: alle ordering-experience; Erhalt von profile-security / Eigenes Profil und area-access / Self-Service. Tests: MealOrdering/MealVariants/MealPreferences/WeekNavigation/Dashboard/Ical; Playwright Desktop/Mobil und gezielte Accessibilityprüfung.

- [x] 5.1 Tages-/Wochenauswahl, URL-Filter, Karten, Varianten, Menge und zusammenhängenden Bestellstatus implementieren; Query-Wiederholungen entfernen.
- [x] 5.2 Dashboard, eigene Bestellungen, Guthaben und Verläufe übernehmen; Fehler-, Lade- und Sitzungszustände testen.
- [x] 5.3 Versionierte Assets und Offlinehinweis integrieren; alte Service-Worker-Caches kontrolliert ablösen.
- [x] 5.4 Desktop-/Mobilabläufe, Tastatur/Fokus, Produktionsbuild und vollständige betroffene Regression ausführen; Rollback auf abgesicherte Oberfläche proben.

Abnahmebelege, Integrationsreihenfolge, Reviews, Freigabe und Archivierung führt die Hauptsitzung; sie sind keine zusätzlichen Produktpakete. Überschneidungen werden seriell 1 → 2 → 3 → 4 → 5 integriert. Die begrenzte Resource-Parallelisierung in Paket 4 bleibt möglich, sobald gemeinsame Verträge integriert sind.
