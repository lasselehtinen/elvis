# Installation
------------

## Step 1

Add the package to your `composer.json` and run `composer update`.

    {
        "require": {
            "lasselehtinen/elvis": "dev-master"
        }
    }

## Step 2

Add the service provider and alias in `app/config/app.php`:
    
    'providers' => array(
        ...
        'Lasselehtinen\Elvis\ElvisServiceProvider'
    ),
    
And

    'aliases' => array(
        ...
        'Elvis'           => 'Lasselehtinen\Elvis\Facades\Elvis'
    ),

## Step 3
Publish the package config file by running:

    php artisan config:publish lasselehtinen/elvis
    
Edit your `app/config/packages/lasselehtinen/elvis/config.php` and change the default Elvis REST API endpoint URI and the username and password.

# Usage

## List of functions

 - [Login](#login)
 - [Browse](#browse)
 - [Search](#search)
 - [Profile](#profile)
 - [Create](#create)
 - [Logout](#logout)

### <a name="login">Login</a>
You need to login as the first step. Store the session_id returned by the function and pass it to further requests.

    $session_id = Elvis::login();
    $search_results = Elvis::search($session_id, 'gtin:9789510123454');

### <a name="browse">Browse</a>
This call is designed to allow you to browse folders and show their subfolders and collections, similar to how folder browsing works in the Elvis desktop client. Read more at https://elvis.tenderapp.com/kb/api/rest-browse.

> Note: Even though it is possible to return the assets in folders, doing so is not advised. The browse call does not limit the number of results, so if there are 10000 assets in a folder it will return all of them. It is better to use a search to find the assets in a folder and fetch them in pages.

    $browse_results = Elvis::browse($session_id, '/Folder/');

Parameter | Description
--------- | -----------
session_id| Session ID returned by the login function.
path | The path to the folder in Elvis you want to list. Path is automatically encoded.
fromRoot | Allows returning multiple levels of folders with their children. When specified, this path is listed, and all folders below it up to the 'path' will have their children returned as well. This ability can be used to initialize an initial path in a column tree folder browser with one server call.
includeFolders | Indicates if folders should be returned.
includeAsset | Indicates if files should be returned.
includeExtensions | A comma separated list of file extensions to be returned. Specify 'all' to return all file types.
            
### <a name="search">Search</a>
Wrapper for the search API, returns the hits found. Facets are not currently supported. You can find more information at https://elvis.tenderapp.com/kb/api/rest-search. You can find details about the function parameters below.

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

### <a name="profile">Profile</a>
Retrieve details about the authenticated user.

    $profile = Elvis::profile($session_id);

### <a name="create">Create</a>
This call will create a new asset in Elvis. It can be used to upload files into Elvis. It can also be used to create 'virtual' assets like collections. In that case no file has to be uploaded and Elvis will create a 0 kb placeholder for the virtual asset. Read more at https://elvis.tenderapp.com/kb/api/rest-create.

**Note:** Either assetPath, filename or name as to be specified in the metadata.

    $create = Elvis::create($session_id, './file.txt', array('assetPath' => '/Users/demouser/filename.txt'));

Parameter | Description
--------- | -----------
session_id| Session ID returned by the login function.
Filedata|The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used. Please note that in this case, you give the local filepath as a parameter. The wrapper will then convert it multipart/file.
metadata|Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
   
### <a name="logout">Logout</a>
It is a good practice to close the session after you are done with your queries so it doesn't take API licences unnecessarily. You can use logout for this.

    $logout = Elvis::logout($session_id);
   
