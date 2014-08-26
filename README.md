## Installation
------------

### Step 1

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "lasselehtinen/elvis": "dev-master"
        }
    }

### Step 2

Add the service provider and alias in `app/config/app.php`:
    
	'providers' => array(
        ...
        'Lasselehtinen\Elvis\ElvisServiceProvider'
    ),
    
And

    'aliases' => array(
        ...
        'Elvis'			  => 'Lasselehtinen\Elvis\Facades\Elvis'
    ),

### Step 3
Publish the package config file by running:

    php artisan config:publish lasselehtinen/elvis
    
Edit your `app/config/packages/lasselehtinen/elvis/config.php` and change the default Elvis REST API endpoint URI and the username and password.

## Usage

### Login
You need to login as the first step. 
