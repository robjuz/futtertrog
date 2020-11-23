# Futtertrog

Meal ordering management system

## Supported providers
| Provider | Prices | Auto ordering |
| -------- | ------ | ------------- |
| Holzke | yes  | yes |
| Call A Pizza | no | no
---

## Deployment

### Classic

* ```git clone https://github.com/robjuz/futtertrog.git```
* ```composer install```
* ```cp .env.example .env```
* edit `.env`
* setup webserver

## Local Development

### Debugging
to enable general debuging logs, set the `APP_DEBUG=true` environment variable

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

`docker-compose up -d`'

After first install you need to generate the APP_KEY

* `docker-compose exec futtertrog cp .env.example .env`
* `docker-compose exec futtertrog php artisan key:gen`


Visit http://localhost:8080 in your browser

#### Mails

You can see your mails using __MailHog__ under ```localhost:8025```

### PHPUnit

```vendor/bin/phpunit --configuration ./phpunit.xml```

#### PhpStorm
* Go to PHP settings and set default interpreter to the futtertrog service.
* In the Lifecycle option select "Connect to existing container" 
* `docker-compose up -d`

Now you can run phpunit with the ide

---

## Login with GitLab

You can use gitlab as OAuth provider
* set `LOGIN_WITH_GITLAB=true`
* go to GitLab -> Applications and create a new App
* set `GITLAB_CLIENT_ID`, `GITLAB_CLIENT_SECRET` and `GITLAB_URL`

## SMS with Nexmo
You can use Nexmo to send SMS notifications to admin users

Set the `NEXMO_KEY` and `NEXMO_SECRET`

## Holzke
To get prices you need to set you login data.

Set `HOLZKE_LOGIN` and `HOLZKE_PASSWORD`

you can set `HOLZKE_SCHEDULE=true` to enable automatic pulling for new order possibilities

## Call a Pizza
to change the default location set `CALL_A_PIZZA_LOCATION`

you can get the location from the url e.g __dresden_pieschen__ from https://www.call-a-pizza.de/dresden_pieschen/
