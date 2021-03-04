# futtertrog

An easy system to organize your and your coworkers lunch orders.

[![Build Status](https://travis-ci.org/robjuz/futtertrog.svg?branch=master)](https://travis-ci.org/robjuz/futtertrog)
[![codecov](https://codecov.io/gh/robjuz/futtertrog/branch/master/graph/badge.svg)](https://codecov.io/gh/robjuz/futtertrog)
[![StyleCI](https://github.styleci.io/repos/159231011/shield?branch=master)](https://github.styleci.io/repos/159231011)

## Local development

### First run
```
cp .env.example .env
docker-compose run --rm futtertrog php artisan key:gen
```
 
### Every nex run
`docker-compose up -d` danach im Webbrowser http://localhost:8001 besuchen.

## Admin Account

```
docker-compose exec futtertrog php artisan db:seed
``` 

Will create a `admin@example.com` user with `123456` password. You can change this password in the app.
