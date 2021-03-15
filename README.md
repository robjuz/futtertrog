# futtertrog

An easy system to organize your and your coworkers lunch orders.

[![Build Status](https://travis-ci.org/robjuz/futtertrog.svg?branch=master)](https://travis-ci.org/robjuz/futtertrog)
[![codecov](https://codecov.io/gh/robjuz/futtertrog/branch/master/graph/badge.svg)](https://codecov.io/gh/robjuz/futtertrog)
[![StyleCI](https://github.styleci.io/repos/159231011/shield?branch=master)](https://github.styleci.io/repos/159231011)

## Installation

### Using git

### Using docker-compose

#### Generate APP_KEY
    docker run --rm robjuz/futtertrog php artisan key:gen --show

#### Create a `docker-compose.yml` file

Remember to set __APP_KEY__ generated in previous step 

Please also set you __APP_URL__ and __SMTP__ credentials

```dockerfile
version: '3'

services:
  futtertrog:
    image: robjuz/futtertrog
    ports:
      - 80:80
    depends_on:
      - db
    environment:
      APP_NAME: Futtertrog
      APP_KEY: <APP_KEY>
      APP_URL:<APP_URL>
      
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_DATABASE: futtertrog
      DB_USERNAME: futtertrog
      DB_PASSWORD: futtertrog
      
      MAIL_DRIVER: smtp
      MAIL_HOST: <MAIL_HOST>
      MAIL_PORT: 2525
      MAIL_USERNAME: <MAIL_USER>
      MAIL_PASSWORD: <MAIL_PASSWORD>
      MAIL_ENCRYPTION: null
      
      HOLZKE_LOGIN: 
      HOLZKE_PASSWORD: 
      HOLZKE_SCHEDULE: "false"
      
      STARTUP_COMMAND_1: sleep 10 && php artisan migrate --force
      CRON_SCHEDULE_1: "* * * * *"
      CRON_COMMAND_1: php artisan schedule:run
  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: futtertrog
      MYSQL_USER: futtertrog
      MYSQL_PASSWORD: futtertrog
      MYSQL_RANDOM_ROOT_PASSWORD: 1
    volumes:
      - mysql:/var/lib/mysql

volumes:
  mysql:
```

#### Secure it with SSL

You could use nginx as reverse proxy and certbot to manage you certificate  

## Meal providers

The system currently support 3 providers

* Holzke
* Call a Pizza
* Manuall

### Holzke

To import all available menus for the incoming days run:

    php artisan import:holzke

If you with to also import prices please set you Holzke credentials in `.env` file

    HOLZKE_LOGIN: 
    HOLZKE_PASSWORD: 

If you with to automatically get available menus set 

    HOLZKE_SCHEDULE: "true"    

### Call A Pizza

Set you location using the `CALL_A_PIZZA_LOCATION` variable.

To obtain the local key 
1) Visit https://www.call-a-pizza.de
2) Enter you postcode
3) Get the location key from the url. ex. `dresden_loebtau_sued`

## Local development

### First run

    cp .env.example .env
    docker-compose run --rm futtertrog php artisan key:gen
    docker-compose up -d

 
### Every nex run

    docker-compose up -d

then visit `http://localhost:8001` in your browser.

## Admin Account


    docker-compose exec futtertrog php artisan db:seed
 

Will create a `admin@example.com` user with `123456` password. You can change this password in the app.
