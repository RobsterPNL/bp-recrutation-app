# Bigger Picture APP

## Run the Application

1. Clone the repository and `cd` into it.
1. Run `docker-compose up -d` to start the containers.
1. Run docker `exec -it bp_app-php-fpm /bin/bash`.
1. In docker container run:

    ```bash
    $ composer install
    ```

1. Create `postgres` database.
1. In docker container run
    ```bash
    $ php artisan migrate
    ```
1. Run `ngrok http 80` and copy "Forwarding" URL to https://www.twilio.com/console/authy/applications/246572/push-authentication push configuration.
1. Once ngrok is running, open up your browser and go to your ngrok URL. It will look something like this: `http://9a159ccf.ngrok.io`
