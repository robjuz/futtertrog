# futtertrog

Ein einfaches System um Essenbestellungen zu verwalten

[![Build Status](https://travis-ci.org/robjuz/futtertrog.svg?branch=master)](https://travis-ci.org/robjuz/futtertrog)
[![codecov](https://codecov.io/gh/robjuz/futtertrog/branch/master/graph/badge.svg)](https://codecov.io/gh/robjuz/futtertrog)
[![StyleCI](https://github.styleci.io/repos/159231011/shield?branch=master)](https://github.styleci.io/repos/159231011)

## Entwicklungsumgebung

`docker-compose up` danach im Webbrowser http://localhost:8001 besuchen.

## DB und Admin Benutzer

`docker exec futtertrog php artisan migrate:fresh --seed` erstellt einen `admin@example.com` Benutzer mit Passwort `123456`
