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

## List of supported functions

 - [Login](#login)
 - [Browse](#browse)
 - [Search](#search)
 - [Profile](#profile)
 - [Create](#create)
 - [Update](#update)
 - [Updatebulk](#updatebulk)
 - [Move / rename](#move)
 - [Copy](#copy)
 - [Remove](#remove)
 - [Create folder](#createfolder)
 - [Create relation](#createrelation)
 - [Remove relation](#removerelation)
 - [Query stats](#querystats)
 - [Log usage stats](#logusagestats)
 - [Messages / Localization](#messages)
 - [Checkout](#checkout)
 - [Undocheckout](#undocheckout)
 - [CreateAuthKey](#createAuthKey)
 - [UpdateAuthKey](#updateAuthKey)
 - [RevokeAuthKeys](#revokeAuthKeys)
 - [Logout](#logout)

### <a name="login">Login</a>
You need to login as the first step. Store the sessionId returned by the function and pass it to further requests.

    $sessionId = Elvis::login();
    $search_results = Elvis::search($sessionId, 'gtin:9789510123454');

### <a name="browse">Browse</a>
This call is designed to allow you to browse folders and show their subfolders and collections, similar to how folder browsing works in the Elvis desktop client. Read more at https://elvis.tenderapp.com/kb/api/rest-browse.

> Note: Even though it is possible to return the assets in folders, doing so is not advised. The browse call does not limit the number of results, so if there are 10000 assets in a folder it will return all of them. It is better to use a search to find the assets in a folder and fetch them in pages.

    $browse_results = Elvis::browse($sessionId, '/Folder/');

Parameter | Description
--------- | -----------
sessionId| Session ID returned by the login function.
path | The path to the folder in Elvis you want to list. Path is automatically encoded.
fromRoot | Allows returning multiple levels of folders with their children. When specified, this path is listed, and all folders below it up to the 'path' will have their children returned as well. This ability can be used to initialize an initial path in a column tree folder browser with one server call.
includeFolders | Indicates if folders should be returned.
includeAsset | Indicates if files should be returned.
includeExtensions | A comma separated list of file extensions to be returned. Specify 'all' to return all file types.
            
### <a name="search">Search</a>
Wrapper for the search API, returns the hits found. You can find more information at https://elvis.tenderapp.com/kb/api/rest-search. You can find details about the function parameters below.

**Simple search:**

    $search_results = Elvis::search($sessionId, 'gtin:9789510123454');

Parameter | Description
--------- | -----------
sessionId| Session ID returned by the login function.
q | Actual Lucene query, you can find more details in https://elvis.tenderapp.com/kb/technical/query-syntax
start | First hit to be returned. Starting at 0 for the first hit. Used to skip hits to return 'paged' results. Default is 0.
num | Number of hits to return. Specify 0 to return no hits, this can be useful if you only want to fetch facets data. Default is 50.
sort | The sort order of returned hits. Comma-delimited list of fields to sort on. Read more at https://elvis.tenderapp.com/kb/api/rest-search
metadataToReturn | Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://elvis.tenderapp.com/kb/api/rest-search
appendRequestSecret | When set to true will append an encrypted code to the thumbnail, preview and original URLs.
facetsToReturn | Comma-delimited list fields to return facets for.
facetSelection) | Array of facets and values with the facet as the key and the comma-delimited list of values that should be 'selected' for a given facet as the value. For example ['tags' => 'beach,ball', 'assetDomain' => 'image,video'].


### <a name="profile">Profile</a>
Retrieve details about the authenticated user.

    $profile = Elvis::profile($sessionId);

### <a name="create">Create</a>
This call will create a new asset in Elvis. It can be used to upload files into Elvis. It can also be used to create 'virtual' assets like collections. In that case no file has to be uploaded and Elvis will create a 0 kb placeholder for the virtual asset. Read more at https://elvis.tenderapp.com/kb/api/rest-create.

**Note:** Either assetPath, filename or name as to be specified in the metadata.

    $create = Elvis::create($sessionId, './file.txt', array('assetPath' => '/Users/demouser/filename.txt'));

Parameter | Description
--------- | -----------
sessionId| Session ID returned by the login function.
filename|The local filename to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used. Please note that in this case, you give the local filepath as a parameter. The wrapper will then convert it multipart/file.
metadata|Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.

### <a name="update">Update</a>
This call updates an existing asset in Elvis with a new file. It can also be used to update metadata. Works pretty much the same ways a create. Only difference is that you given additional parameter, the asset id. Read more at https://elvis.tenderapp.com/kb/api/rest-update.

    $update = Elvis::update($sessionId, '1_OSDdstqxbACb97Vd-ret', null, array('Description' => 'Nice view'));

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
id|Elvis asset id to be updated
filename | The file that will replace the current file. Define as null if you just want to update metadata.
metadata | Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.

### <a name="update">Updatebulk</a>
This call updates the metadata of multiple existing assets in Elvis.

*Available since Elvis 3.1*

    $updatebulk = Elvis::updatebulk($sessionId, 'tags:animal', array('status' => 'Correction'));

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
q|A query matching the assets that should be updated
metadata | Array containing the metadata for the assets as an array. Key is the metadata field name and value is the actual value.
async| When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.

Returns either processedCount or processId depending on the value of async.

### <a name="move">Move / rename</a>
Move or rename a folder or a single asset. You can combine a rename operation and a move operation. Just specify the new target path.

When you move or rename a folder, all assets contained in the folder will also be moved to the new location. The subfolder structure will be kept intact.

    $rename = Elvis::move($sessionId, '/Path/to/asset/filename.ext', '/Path/to/asset/new-filename.ext');

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
source | Either a folderPath or assetPath of the folder or asset to be moved or renamed.
target | The folderPath or assetPath to which the folder or asset should be moved or renamed. If the parent folder is the same as in the source path, the asset will be renamed, otherwise it will be moved.)
folderReplacePolicy | Policy used when destination folder already exists. Aither AUTO_RENAME (default), MERGE or THROW_EXCEPTION.
fileReplacePolicy | Policy used when destination asset already exists. Either AUTO_RENAME (default), OVERWRITE, OVERWRITE_IF_NEWER, REMOVE_SOURCE, THROW_EXCEPTION or DO_NOTHING 
filterQuery | When specified, only source assets that match this query will be moved.
flattenFolders | When set to true will move all files from source subfolders to directly below the target folder. This will 'flatten' any subfolder structure.

Please see https://elvis.tenderapp.com/kb/api/rest-move-rename for more information about the folderReplacePolicy and fileReplacePolicy.

### <a name="copy">Copy</a>
Copy a folder or a single asset.

When you copy a folder, all subfolders and assets contained in it will also be copied to the new location. The subfolder structure will be kept intact unless you set flattenFolder to true.

    $copy = Elvis::copy($sessionId, '/Path/to/asset/filename.ext', '/Path/to/asset/copy-filename.ext');

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
source | Either a folderPath or assetPath of the folder or asset to be moved or renamed.
target | The folderPath or assetPath to which the folder or asset should be moved or renamed. If the parent folder is the same as in the source path, the asset will be renamed, otherwise it will be moved.)
folderReplacePolicy | Policy used when destination folder already exists. Aither AUTO_RENAME (default), MERGE or THROW_EXCEPTION.
fileReplacePolicy | Policy used when destination asset already exists. Either AUTO_RENAME (default), OVERWRITE, OVERWRITE_IF_NEWER, REMOVE_SOURCE, THROW_EXCEPTION or DO_NOTHING 
filterQuery | When specified, only source assets that match this query will be moved.
flattenFolders | When set to true will move all files from source subfolders to directly below the target folder. This will 'flatten' any subfolder structure.

Please see https://elvis.tenderapp.com/kb/api/rest-copy for more information about the folderReplacePolicy and fileReplacePolicy.

Returns either processedCount or processId depending on the value of async.

### <a name="remove">Remove</a>
Remove one or more assets. This will remove only assets, no folders.

    $ids = array('1_OSDdstqxbACb97Vd-ret', '1wefOS6bauK8uRxi0rn9EK');
    $remove = Elvis::remove($sessionId, null, $ids, null, false);

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
q | A query that matches all assets to be removed. Be careful with this and make sure you test your query using a search call to prevent removing assets that you did not want to be removed.
   ids| Array containing the assetId's for the assets to be removed. Be careful with this and make sure you test your query using a search call to prevent removing assets that you did not want to be removed.
folderPath | The folderPath of the folder to remove. All assets and subfolders will be removed.
async| When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.

Either 'q' or 'ids' or 'folderPath' must be specified.

Returns either processedCount or processId depending on the value of async.

### <a name="createfolder">Create folder</a>
Remove one or more assets. This will remove only assets, no folders.

    $createFolder = Elvis::createFolder($sessionId, '/Users/lasleh/New');

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
path | The full folderPath of the folder to be created.

Returns an object with the olderPaths of each folder as key with the corresponding result as value (always a string). The following results are possible:

 - "created"
 - "already exists"
 - "access denied"
 - an error message indicating why the folder could not be created
 - 
### <a name="createrelation">Create relation</a>
Remove one or more assets. This will remove only assets, no folders.

    $createRelation = Elvis::createRelation($sessionId, 'contains', 'FWiH0ipWKVl8CkbFGm9me9', 'CFN7pN2S4GFBz4Vorc34VJ');

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
relationType | The type of relation to create. Read more at https://elvis.tenderapp.com/kb/content-management/relations
target1Id | The id of the asset on one side of the relation.
target2Id | The id of the asset on one side of the relation.
metadata |A JSON encoded object with properties that match Elvis relation metadata field names. This metadata will be set on the relation in Elvis.
    
The operation returns an empty 200 OK status. If the operation fails, an error page with a 500 error status will be returned.

### <a name="removerelation">Remove relation</a>
Remove one or more relations between assets.

    $removeRelation = Elvis::removeRelation($sessionId, ['77-nZwDXaTJ96lhhaDvp0t']);

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
relationIds| Array containing relation id's to be removed. To find the relation ids, use a relation search. https://elvis.tenderapp.com/kb/api/rest-search
    
The operation returns an empty 200 OK status.

If the operation fails, an error page with a 500 error status will be returned.

### <a name="querystats">Query stats</a>
Query stats database for usage statistics.

    $queryStats = Elvis::queryStats($sessionId, 'path_to/query.sql', 10);

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for     queryFile | The path to the SQL file with the query you want to run.
num | Number of rows to return. Specify 0 to return all rows.
additionalQueryParameters | Array of additional query parameters passed to the SQL in name => value format.
    
For more details about the parameters see https://elvis.tenderapp.com/kb/api/rest-query-stats. Returns the result of the SQL query as an object. 

### <a name="logusagestats">Log usage stats</a>
Logs an entry in the stats database for usage statistics about assets. A record will be added to the "usage_log" table, see [Query stats](#querystats) for details.

    $logUsage = Elvis::logUsage($sessionId, 'FWiH0ipWKVl8CkbFGm9me9', 'CUSTOM_ACTION_test', array('metadataKey'=>'value'));

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
assetId | The id of the asset for which the action is logged.
action | Name of the action that is logged. This must start with "CUSTOM_ACTION_", if it does not, this prefix will be added to the logged action name.
additionalQueryParameters | Array of additional query parameters that are logged as details for the action.
    
This call does not return a value, it only returns an http 200 status OK.

### <a name="messages">Messages / Localization</a>
Retrieve message bundles from the Elvis server.

Allows localization of a custom plugin using messages available on the server. The messages from the webclient web or desktop client acm can be used in your own plugin. It is also possible to use any custom messages defined in the Config/messages folder.

The common message bundle cmn is always returned and merged with the requested bundle. The common bundle contains messages for relations, metadata fields, metadata groups and metadata values. The common message keys have the following structure:

**Relations:**

    relation.[relation type].label
**Metadata fields:**

    field_label.[field name]

**Metadata groups:**

    field_group_label.[group name]

**Metadata values:**

    field_value_label.[field name].[value]    
    
For a full list of available messages see this [knowledge base article](https://elvis.tenderapp.com/kb/server-administration/translating-clients).    

     $messages = Elvis::messages($sessionId, 'fi_FI');

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function. This is used for further queries towards Elvis
localChain | Array containing list of locales, the first supplied locale is leading. If a message is missing for a locale it will fall back to the next locale in the chain for that message.
ifModifiedSince | The date of the last requested cached messages, specified in milliseconds since the standard base time known as "the epoch", namely January 1, 1970, 00:00:00 GMT.
bundle | The bundle to return, can be either web or acm. The cmn bundle will always be returned combined with the requested bundle.

    
The service returns an object containing all keys and messages. Please note that commas in object properties have to be referenced like this:

    $messages->{'field_label.creatorEmail'}

### <a name="checkout">Checkout</a>
Checks out an asset from the system locking the file for other users.

    $checkout= Elvis::checkout($sessionId, $assetId);

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function.
assetId | The Elvis id of the asset to be checked out.
    
This will return the checkout metadata in the response as an object.

### <a name="undocheckout">Undocheckout</a>
Undo a checkout for a single asset

    $checkout = Elvis::undocheckout($sessionId, $assetId);

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function.
assetId | Elvis id of the asset that was checked out.

### <a name="createAuthKey">CreateAuthKey</a>
Create an authKey in Elvis.

    $createAuthKey = Elvis::createAuthKey($sessionId, 'Test', '2999-01-01', array($assetId));

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function.
subject | AuthKey subject
validUntil | Expiry date, in one of the date formats supported by Elvis. See https://elvis.tenderapp.com/kb/technical/query-syntax for more details
assetIds | Array of of asset id's to share, do not specify for a pure upload request (requestUpload must be true is this case)
description | AuthKey description that will be shown to receiver of the link.
downloadOriginal | Allow downloading original files. Setting this to true will automatically force downloadPreview to true as well.
downloadPreview | Allow viewing and downloading previews. Setting this to false will only show thumbnails and will also force downloadOriginal to false.
requestApproval | Request for approval.
requestUpload | Allow uploading new files, must be true when asset id's is not specified.
containerId | Container asset id which uploaded files are related to. Only relevant when requestUpload=true.
importFolderPath | folderPath where files are uploaded. Required when requestUpload=true.
notifyEmail | Email address to send notifications to when upload or approval is finished. Only relevant when requestUpload=true or requestApproval=true.
sort | Client setting, specify a comma-delimited list of fields to sort the results on. Follows the same behavior as sort in REST - search call
viewMode | Client setting. Possible values 'thumbnail', 'list' or 'mason'.
thumbnailFields | Client setting, array containing list of fieldnames for showing metadata in the thumbnail view.
listviewFields | Client setting, array containing list of fieldnames for showing metadata in the list view.
filmstripFields | Client setting, array containing list of fieldnames for showing metadata in the filmstrip view.
thumbnailZoomLevel | Client setting, thumbnail zoom level in the thumbnail view.
listviewZoomLevel | Client setting, thumbnail zoom level in the list view.
filmstripZoomLevel | Client setting, thumbnail zoom level in the filmstrip view.

Returns object containing the authKey and links to different clients.

### <a name="updateAuthKey">UpdateAuthKey</a>
Update an authKey in Elvis.

With this API call it is possible to update certain properties of an authKey. Please note that it is not possible to add or remove assets from an authKey once it has been created.

    $updateAuthKey = Elvis::updateAuthKey($sessionId, $authKey, 'Test', '2999-02-02');

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function.
key | The authKey which will be updated.
subject | AuthKey subject
validUntil | Expiry date, in one of the date formats supported by Elvis. See https://elvis.tenderapp.com/kb/technical/query-syntax for more details
description | AuthKey description that will be shown to receiver of the link.
downloadOriginal | Allow downloading original files. Setting this to true will automatically force downloadPreview to true as well.
downloadPreview | Allow viewing and downloading previews. Setting this to false will only show thumbnails and will also force downloadOriginal to false.
requestApproval | Request for approval.
requestUpload | Allow uploading new files, must be true when asset id's is not specified.
containerId | Container asset id which uploaded files are related to. Only relevant when requestUpload=true.
importFolderPath | folderPath where files are uploaded. Required when requestUpload=true.
notifyEmail | Email address to send notifications to when upload or approval is finished. Only relevant when requestUpload=true or requestApproval=true.
sort | Client setting, specify a comma-delimited list of fields to sort the results on. Follows the same behavior as sort in REST - search call
viewMode | Client setting. Possible values 'thumbnail', 'list' or 'mason'.
thumbnailFields | Client setting, array containing list of fieldnames for showing metadata in the thumbnail view.
listviewFields | Client setting, array containing list of fieldnames for showing metadata in the list view.
filmstripFields | Client setting, array containing list of fieldnames for showing metadata in the filmstrip view.
thumbnailZoomLevel | Client setting, thumbnail zoom level in the thumbnail view.
listviewZoomLevel | Client setting, thumbnail zoom level in the list view.
filmstripZoomLevel | Client setting, thumbnail zoom level in the filmstrip view.

Returns object containing the authKey and links to different clients.

### <a name="revokeAuthKeys">RevokeAuthKeys</a>
Create an authKey in Elvis.

    $revokeAuthKeys = Elvis::revokeAuthKeys($sessionId, array($assetId));

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function.
keys | list of authKeys.

Returns an empty object if succesfull.
   
### <a name="logout">Logout</a>
It is a good practice to close the session after you are done with your queries so it doesn't take API licences unnecessarily. You can use logout for this.

    $logout = Elvis::logout($sessionId);

Parameter | Description
--------- | -----------
sessionId | Session ID returned by the login function.
   
