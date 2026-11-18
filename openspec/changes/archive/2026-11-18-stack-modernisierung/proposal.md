# Futtertrog modernisieren

Revision: r2 · Profil: standard · Status: Plan vollständig, nicht zur Umsetzung freigegeben.

## Why

Normale Bestellabläufe und administrative Eingriffe sind in Navigation, Controllern und Endpunkten vermischt. Nutzer benötigen eine eigene Profilansicht mit sicherer Passwortverwaltung. Die Bestelloberfläche braucht eine flüssige mobile Bedienung und zuverlässige Rückmeldung. Laravel 13, PHP 8.5 und MariaDB sind bereits geeignete Grundlagen.

## What Changes

Ein zusammenhängendes Vorhaben in der vom Nutzer vorgegebenen Reihenfolge (E1, E2):

| Phase | Ergebnis | Schätzung |
| --- | --- | --- |
| 1. Bereiche und Berechtigungen | Self-Service und Administration serverseitig und visuell trennen; bisherige Verwaltungsansichten zunächst weiterverwenden | 3–5 Personentage |
| 2. Profil und Passwort | Konto, Sicherheit, Einstellungen; bestätigte lokale/externe Anmeldemethoden; Vue/Inertia-Grundgerüst | 5–8 Personentage |
| 3. Administration | Eigenes Filament-Panel für Benutzer, Angebote, Importe, Bestellungen, Finanzen und Mitteilungen | 7–11 Personentage |
| 4. Bestelloberfläche | Mobile Vue/Inertia-Oberfläche einschließlich Dashboard, Bestellverlauf, Fehlerzuständen und PWA-Updates | 7–11 Personentage |

Gesamt vorläufig **22–35 Personentage**, bei einer Person ungefähr **5–7 Arbeitswochen**, zuzüglich Rückmeldungen und Wartezeiten. Keine Termin- oder Kostenfreigabe. Unbekannter Zustand realer Bestandsdaten und Anbieterzugänge kann die Schätzung verändern; produktive Daten werden für die Planung nicht gelesen.

Bestehende Anmeldewege bleiben erhalten. Ein zusätzliches lokales Passwort wird erst nach sicherer Bestätigung der Kontoinhaberschaft eingerichtet (E3). Eine zusätzliche Bestätigung beim Übergang ungeklärter Bestandskonten verhindert, dass früher aus externen IDs abgeleitete Passwörter ungeprüft als bewusste lokale Passwörter gelten.

## Entscheidungen

- Laravel 13/PHP 8.5/MariaDB weiterverwenden.
- Nutzerbereich: Vue 3, TypeScript, Inertia 3, Vite 8, Tailwind 4 und ausgewählte shadcn-vue-Komponenten.
- Adminbereich: Filament 5/Livewire 4 unter `/admin`, eigene Navigation und Asset-Einstiege, gemeinsame Laravel-Sitzung und Fachlogik.
- Bestehendes `is_admin` genügt; keine zusätzlichen Rollen und kein Rollenframework.
- Profil zunächst mit editierbarem Namen, angezeigter E-Mail, Passwortverwaltung und bestehenden Einstellungen. Freie E-Mail-Änderung bleibt außerhalb dieses Vorhabens.
- Zusatzaufwand zweier Oberflächentechniken wird zugunsten passender Nutzer- und Verwaltungswerkzeuge akzeptiert. Paketkompatibilität ist durch einen isolierten Composer-Dry-Run grundlegend bestätigt, Laufzeitintegration noch nicht.

## Non-goals

Kein Backend-Neubau, Datenbankwechsel, native App, Offlinebestellen, Bezahlsystem, Buchhaltungssystem, zusätzliche Rollen, öffentliche Registrierung, umfassender Security-Audit oder API-Neubau. Keine Änderungen am stabilen 1.x-Zweig. Kein Deployment, keine echten Bestellungen, E-Mails oder produktiven Datenmigrationen während Planung und Tests.

## Impact

Betroffen sind Webrouten, Policies, Authentifizierung, Benutzer-/Identitätsdaten, gemeinsame Bestellaktionen, beide Oberflächen sowie Build und CI. Bestehende Anbieteranbindungen, Geldberechnung, Scheduler, persönliche Einstellungen und Kalenderzugang bleiben funktional erhalten. Details zu Datenübergang und Rücknahme stehen im Design; verbindliche Produktabnahme ausschließlich in den Delta-Spezifikationen.

Nach der Planung folgen gesondert Umsetzungsfreigabe, isolierte Implementierung, Tests und Abschlussprüfung. Die vier Phasen sind keine vier zusätzlich bestätigten Changes und vervielfachen keine Prüfbudgets.
