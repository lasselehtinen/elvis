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
You need to login as the first step. Store the session_id returned by the function and pass it to further requests.

    $session_id = Elvis::login();
    $search_results = Elvis::search($session_id, 'gtin:9789510123454');

### Search
Wrapper for the search API, returns the hits found. You can find more information at https://elvis.tenderapp.com/kb/api/rest-search. You can find details about the function parameters below.

**Simple search:**

    $search_results = Elvis::search($session_id, 'gtin:9789510123454');

Parameter | Description
--------- | -----------
session_id| Session ID returned by the login function.
q | Actual Lucene query, you can find more details in https://elvis.tenderapp.com/kb/technical/query-syntax
start | First hit to be returned. Starting at 0 for the first hit. Used to skip hits to return 'paged' results. Default is 0.
num | Number of hits to return. Specify 0 to return no hits, this can be useful if you only want to fetch facets data. Default is 50.
sort | The sort order of returned hits. Comma-delimited list of fields to sort on. Read more at https://elvis.tenderapp.com/kb/api/rest-search
metadataToReturn | Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://elvis.tenderapp.com/kb/api/rest-search
appendRequestSecret | When set to true will append an encrypted code to the thumbnail, preview and original URLs.

