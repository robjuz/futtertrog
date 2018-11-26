# Futtertrog

An easy system to organize yours and your coworkers lunch orders.

## Installation

### Using git

* ```git clone https://github.com/robjuz/futtertrog.git```
* ```composer install```
* ```cp .env.example .env```
* edit `.env`
* setup webserver


### Using docker-compose

#### Generate APP_KEY
    docker run --rm robjuz/futtertrog php artisan key:gen --show

#### Create a `docker-compose.yml` file

Remember to set __APP_KEY__ generated in previous step

Please also set you __APP_URL__ and __SMTP__ credentials

```yaml
services:
  app:
    image: robjuz/futtertrog
    depends_on:
      db:
        condition: service_healthy
    environment:
      APP_NAME: Futtertrog
      APP_KEY: <APP_KEY>
      APP_URL: <APP_URL>
      
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_DATABASE: futtertrog
      DB_USERNAME: futtertrog
      DB_PASSWORD: futtertrog

      MAIL_MAILER: smtp
      MAIL_HOST: <MAIL_HOST>
      MAIL_PORT: 2525
      MAIL_USERNAME: <MAIL_USER>
      MAIL_PASSWORD: <MAIL_PASSWORD>
      MAIL_ENCRYPTION: null
      
      HOLZKE_ENABLED: "true"
      HOLZKE_LOGIN: 
      HOLZKE_PASSWORD: 
      HOLZKE_SCHEDULE: "false"
      
      STARTUP_COMMAND_1: php artisan migrate --force
      CRON_SCHEDULE_1: "* * * * *"
      CRON_COMMAND_1: php artisan schedule:run
      
  db:
    image: docker.io/bitnami/mariadb:11.5
    environment:
      MARIADB_ROOT_PASSWORD: futtertrog
      MARIADB_DATABASE: futtertrog
      MARIADB_USER: futtertrog
      MARIADB_PASSWORD: futtertrog
    volumes:
      - db:/bitnami/mariadb
    healthcheck:
      test: [ 'CMD', '/opt/bitnami/scripts/mariadb/healthcheck.sh' ]
      interval: 15s
      timeout: 5s
      retries: 6

volumes:
  db:
```

#### Secure it with SSL

You could use nginx as reverse proxy and certbot to manage you certificate

---

## Meal providers

The system currently support 3 providers

| Provider     | Prices | Auto ordering |
|--------------|--------|---------------|
| Holzke       | yes    | yes           |
| Groumetta    | yes    | yes           |
| Call A Pizza | no     | no            |
| Manuall      | no     | no            |

### Holzke

If you with to also import prices please set you Holzke credentials in `.env` file

    HOLZKE_ENABLED: "true" 
    HOLZKE_LOGIN: 
    HOLZKE_PASSWORD: 

If you with to automatically get available menus, set in `.env` file

    HOLZKE_SCHEDULE: "true"    

### Groumetta

If you with to also import prices please set you Holzke credentials in `.env` file

    GOURMETTA_ENABLED: "true" 
    GOURMETTA_LOGIN: 
    GOURMETTA_PASSWORD: 

If you with to automatically get available menus, set in `.env` file

    GOURMETTA_SCHEDULE: "true"    

### Call A Pizza

    CALL_A_PIZZA_ENABLED: "true" 

Set you location using the `CALL_A_PIZZA_LOCATION` variable  in `.env` file

To obtain the location key
1) Visit https://www.call-a-pizza.de
2) Enter you postcode
3) Get the location key from the url. ex. `dresden_loebtau_sued`


### Flaschenpost

    FLASCHENPOST_ENABLED: "true" 

---

## Login with GitLab

You can use gitlab as OAuth provider
1. go to GitLab -> Applications and create a new App
2. set
```
LOGIN_WITH_GITLAB=true
GITLAB_CLIENT_ID=
GITLAB_CLIENT_SECRET=
GITLAB_URL=
```
in `.env` file
---

## Local Development

### Debugging
to enable general debugging logs, set the `APP_DEBUG=true` in the `.env` file

### Classic
* `composer install`
* `cp .env.example .env`
* set you DATABASE credentials in the `.env` file

After first install you need to generate the APP_KEY

```php artisan key:gen```

#### Build-in Webserver
``` php artisan serve```

Visit http://localhost:8000 in your browser

### With Docker Compose

    docker-compose pull
    docker-compose up -d

After first install you need to generate the APP_KEY

* `cp .env.example .env`
* `docker-compose exec app php artisan key:gen`


Visit http://localhost:8080 in your browser

#### Mails

You can see your mails using __MailHog__ under ```localhost:8025```

### PHPUnit

```vendor/bin/phpunit --configuration ./phpunit.xml```

#### PhpStorm
* Go to PHP settings and set default interpreter to the app service.
* In the Lifecycle option select "Connect to existing container" 
* `docker-compose up -d`

Now you can run phpunit with the ide

### Admin Account
    php artisan db:seed

or

    docker-compose exec app php artisan db:seed


Will create a `admin@example.com` user with `123456` password. You can change this password in the app.
