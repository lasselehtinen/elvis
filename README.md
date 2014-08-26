Laravel package for doing REST API queries against Woodwings Elvis DAM (Digital Asset Management)

Installation
------------

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "lasselehtinen/elvis": "*"
        }
    }

Add the service provider in `app/config/app.php`:

    'Lasselehtinen\Elvis\ElvisServiceProvider',
