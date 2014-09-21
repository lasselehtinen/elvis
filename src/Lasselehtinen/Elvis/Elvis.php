<?php namespace LasseLehtinen\Elvis;

// Import classes to use the classic "Config::get()" approach and App for throwing exceptions
use Config;
use App;

class Elvis
{
    /**
    * Login
    *
    * Logins to Elvis with the credentials stored in the config
    *
    * @return (string) Session ID for further queries
    */
    public function login()
    {
        // Form login parameters
        $loginParameters = array(
            'username' => Config::get('elvis::username'),
            'password' => Config::get('elvis::password')
        );

        $response = Elvis::query(null, 'login', $loginParameters);

        return $response->sessionId;
    }

    /**
    * Logout
    *
    * Logouts from Elvis with the given session id
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @return (bool) (logoutSuccess) True if logout was succesfull
    */
    public function logout($sessionId)
    {
        // Call logout REST API
        $response = Elvis::query($sessionId, 'logout');

        return $response->logoutSuccess;
    }

    /**
     * Search
     *
     * Wrapper for the search API, returns the hits found. Facets are not currently supported. You can find more information at https://elvis.tenderapp.com/kb/api/rest-search.
     *
     * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
     * @param (string) (q) Actual Lucene query, you can find more details in https://elvis.tenderapp.com/kb/technical/query-syntax
     * @param (int) (start) First hit to be returned. Starting at 0 for the first hit. Used to skip hits to return 'paged' results. Default is 0.
     * @param (int) (num) Number of hits to return. Specify 0 to return no hits, this can be useful if you only want to fetch facets data. Default is 50.
     * @param (string) (sort) The sort order of returned hits. Comma-delimited list of fields to sort on. Read more at https://elvis.tenderapp.com/kb/api/rest-search
     * @param (string) (metadataToReturn) Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://elvis.tenderapp.com/kb/api/rest-search
     * @param (bool) (appendRequestSecret) When set to true will append an encrypted code to the thumbnail, preview and original URLs.
     * @return (object) List of search results
     */
    public function search($sessionId, $q, $start = 0, $num = 50, $sort = 'assetCreated-desc', $metadataToReturn = 'all', $appendRequestSecret = false)
    {
        // Form search parameters
        $searchParameters = array(
            'q'                     => $q,
            'start'                 => $start,
            'num'                   => $num,
            'sort'                  => $sort,
            'metadataToReturn'      => $metadataToReturn,
            'appendRequestSecret'   => $appendRequestSecret
        );

        // Call the search REST API
        $response = Elvis::query($sessionId, 'search', $searchParameters);

        return $response;
    }

    /**
    * Browse
    *
    * This call is designed to allow you to browse folders and show their subfolders and collections, similar to how folder browsing works in the Elvis desktop client.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (path) The path to the folder in Elvis you want to list.
    * @param (string) (fromRoot) Allows returning multiple levels of folders with their children. When specified, this path is listed, and all folders below it up to the 'path' will have their children returned as well.
    * @param (bool) (includeFolders) Indicates if folders should be returned. Optional. Default is true.
    * @param (bool) (includeAsset) Indicates if files should be returned. Optional. Default is true, but filtered to only include 'container' assets.
    * @param (string) (includeExtensions) A comma separated list of file extensions to be returned. Specify 'all' to return all file types.
    * @return (object) (results) An array of folders and assets.
    */
    public function browse($sessionId, $path, $fromRoot = null, $includeFolders = true, $includeAsset = true, $includeExtensions = '.collection, .dossier, .task')
    {
        // Form browse parameters
        $browseParameters = array(
            'path'              => $path,
            'fromRoot'          => $fromRoot,
            'includeFolders'    => $includeFolders,
            'includeAsset'      => $includeAsset,
            'includeExtensions' => $includeExtensions
        );

        // Call browse REST API
        $response = Elvis::query($sessionId, 'browse', $browseParameters);

          return $response;
    }

    /**
    * Profile
    *
    * Retrieve details about the user authenticated in the current browser session.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @return (object) Profile attached to the session
    */
    public function profile($sessionId)
    {
        // Call profile REST API
        $response = Elvis::query($sessionId, 'profile');

        return $response;
    }

    /**
    * Create
    *
    * Upload and create an asset.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (filename) The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
    * @param (array) (metadata) Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
    * @return (object) Information about the newly created asset
    */
    public function create($sessionId, $filename, $metadata = null)
    {

        $response = Elvis::query($sessionId, 'create', null, $metadata, $filename);

        return $response;
    }

    /**
    * Update
    *
    * Upload and create an asset.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (id) Elvis asset id to be updated
    * @param (string) (filename) The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
    * @param (array) (metadata) Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
    * @return (object) Elvis returns something strange, TODO investigate it
    */
    public function update($sessionId, $id, $filename, $metadata)
    {
        // Form update parameters
        $updateParameters = array(
            'id' => $id,
        );

        $response = Elvis::query($sessionId, 'update', $updateParameters, $metadata, $filename);

        return $response;
    }

    /**
    * Updatebulk
    *
    * This call updates the metadata of multiple existing assets in Elvis.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (q) A query matching the assets that should be updated
    * @param (array) (metadata) Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
    * @param (bool) (async) When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.
    * @return (object) Either processedCount or processId depending if async is true or false
    */
    public function updatebulk($sessionId, $query, $metadata, $async = false)
    {
        // Form updatebulk parameters
        $updateBulk = array(
            'q'        => $query,
            'async'    => $async
        );

         // Do the query
        $response = Elvis::query($sessionId, 'updatebulk', $updateBulk, $metadata);

        return $response;
    }

    /**
    * Move / rename
    *
    * Move or rename a folder or a single asset.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (source) Either a folderPath or assetPath of the folder or asset to be moved or renamed.
    * @param (string) (target) The folderPath or assetPath to which the folder or asset should be moved or renamed. If the parent folder is the same as in the source path, the asset will be renamed, otherwise it will be moved.)
    * @param (string) (folderReplacePolicy) Policy used when destination folder already exists. Aither AUTO_RENAME (default), MERGE or THROW_EXCEPTION.
    * @param (string) (fileReplacePolicy) Policy used when destination asset already exists. Either AUTO_RENAME (default), OVERWRITE, OVERWRITE_IF_NEWER, REMOVE_SOURCE, THROW_EXCEPTION or DO_NOTHING
    * @param (string) (filterQuery) When specified, only source assets that match this query will be moved.
    * @param (bool) (flattenFolders) When set to true will move all files from source subfolders to directly below the target folder. This will 'flatten' any subfolder structure.
    * @return (object) Either processedCount or processId depending if async is true or false
    */
    public function move($sessionId, $source, $target, $folderReplacePolicy = 'AUTO_RENAME', $fileReplacePolicy = 'AUTO_RENAME', $filterQuery = '*:*', $flattenFolders = false)
    {
         // Form move parameters
        $moveParameters = array(
            'source'                => $source,
            'target'                => $target,
            'folderReplacePolicy'   => $folderReplacePolicy,
            'fileReplacePolicy'     => $fileReplacePolicy,
            'filterQuery'           => $filterQuery,
            'flattenFolders'        => $flattenFolders
        );

         // Do the query
        $response = Elvis::query($sessionId, 'move', $moveParameters);

        return $response;
    }

    /**
    * Copy
    *
    * Copy a folder or a single asset.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (source) Either a folderPath or assetPath of the folder or asset to be moved or renamed.
    * @param (string) (target) The folderPath or assetPath to which the folder or asset should be moved or renamed. If the parent folder is the same as in the source path, the asset will be renamed, otherwise it will be moved.)
    * @param (string) (folderReplacePolicy) Policy used when destination folder already exists. Aither AUTO_RENAME (default), MERGE or THROW_EXCEPTION.
    * @param (string) (fileReplacePolicy) Policy used when destination asset already exists. Either AUTO_RENAME (default), OVERWRITE, OVERWRITE_IF_NEWER, REMOVE_SOURCE, THROW_EXCEPTION or DO_NOTHING
    * @param (string) (filterQuery) When specified, only source assets that match this query will be moved.
    * @param (bool) (flattenFolders) When set to true will move all files from source subfolders to directly below the target folder. This will 'flatten' any subfolder structure.
    * @param (bool) (async) When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.
    * @return (object) Either processedCount or processId depending if async is true or false
    */
    public function copy($sessionId, $source, $target, $folderReplacePolicy = 'AUTO_RENAME', $fileReplacePolicy = 'AUTO_RENAME', $filterQuery = '*:*', $flattenFolders = false, $async = false)
    {
        // Form copy parameters
        $copyParameters = array(
            'source'                => $source,
            'target'                => $target,
            'folderReplacePolicy'   => $folderReplacePolicy,
            'fileReplacePolicy'     => $fileReplacePolicy,
            'filterQuery'           => $filterQuery,
            'flattenFolders'        => $flattenFolders,
            'async'                 => $async
        );

         // Do the query
        $response = Elvis::query($sessionId, 'copy', $copyParameters);

        return $response;
    }

    /**
    * Remove
    *
    * Remove one or more assets. This will remove only assets, no folders.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (q) A query that matches all assets to be removed. Be careful with this and make sure you test your query using a search call to prevent removing assets that you did not want to be removed.
    * @param (array) (ids) Array containing the assetId's for the assets to be removed. Be careful with this and make sure you test your query using a search call to prevent removing assets that you did not want to be removed.
    * @param (string) (folderPath) The folderPath of the folder to remove. All assets and subfolders will be removed.
    * @param (bool) (async) When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.
    * @return (object) Either processedCount or processId depending if async is true or false
    */
    public function remove($sessionId, $q = null, $ids = null, $folderPath = null, $async = false)
    {
        if ($ids !== null && is_array($ids)) {
            $idsCommaSeparated = implode(",", $ids);
        } else {
            $idsCommaSeparated = null;
        }

        // Form remove parameters
        $removeParameters = array(
            'q'             => $q,
            'ids'           => $idsCommaSeparated,
            'folderPath'    => $folderPath,
            'async'         => $async
        );

         // Do the query
        $response = Elvis::query($sessionId, 'remove', $removeParameters);

        return $response;
    }

    /**
    * Create folder
    *
    * Create one or more folders.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (path) The full folderPath of the folder to be created. This same parameter name can be specified multiple times to create several folders with one call.
    * @return (object) Information about the newly created folder
    */
    public function createFolder($sessionId, $path)
    {
        // Form createFolder parameters
        $createFolderParameters = array(
            'path'                => $path
        );

        $response = Elvis::query($sessionId, 'createFolder', $createFolderParameters);

        return $response;
    }

 /**
    * Create relation
    *
    * This call creates a relation of a certain type between two assets in Elvis. For example, to add an asset to a collection.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (relationType) The type of relation to create. Read more at https://elvis.tenderapp.com/kb/content-management/relations
    * @param (string) (target1Id) The id of the asset on one side of the relation.
    * @param (string) (target2Id) The id of the asset on one side of the relation.
    * @param (array) (metadata) A JSON encoded object with properties that match Elvis relation metadata field names. This metadata will be set on the relation in Elvis.
    * @return (object) Returns an empty 200 OK status.
    */
    public function createRelation($sessionId, $relationType, $target1Id, $target2Id, $metadata = null)
    {
        // Form createRelation parameters
        $createRelationParameters = array(
            'relationType'  => $relationType,
            'target1Id'     => $target1Id,
            'target2Id'     => $target2Id
        );

        $response = Elvis::query($sessionId, 'createRelation', $createRelationParameters, $metadata);

        return $response;
    }

    /**
    * Remove relation
    *
    * Remove one or more relations between assets.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (array) (relationIds) Array containing relation id's to be removed. To find the relation ids, use a relation search (https://elvis.tenderapp.com/kb/api/rest-search).
    * @return (object) Returns an empty 200 OK status.
    */
    public function removeRelation($sessionId, $relationIds)
    {
        // Form createRelation parameters
        $removeRelationParameters = array(
            'relationIds'  => implode(',', $relationIds)
        );

        $response = Elvis::query($sessionId, 'removeRelation', $removeRelationParameters);

        return $response;
    }

    /**
    * Query stats
    *
    * Query stats database for usage statistics. 
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (queryFile) The path to the SQL file with the query you want to run.
    * @param (integer) (num) Number of rows to return. Specify 0 to return all rows.
    * @param (array) ($additionalQueryParameters) Array of additional query parameters passed to the SQL in name => value format.
    * @return (object) Returns an empty 200 OK status.
    */
    public function queryStats($sessionId, $queryFile, $num = 1000, $additionalQueryParameters = array())
    {
        // Form createRelation parameters
        $queryStatsParameters = array(
            'queryFile' => $queryFile,
            'num'       => $num
        );

        // Add additional parameters
        $queryStatsParameters = array_merge($queryStatsParameters, $additionalQueryParameters);

        $response = Elvis::query($sessionId, 'queryStats', $queryStatsParameters);

        return $response;
    }

    /**
    * Log usage stats
    *
    * Logs an entry in the stats database for usage statistics about assets. A record will be added to the "usage_log" table, see method query stats for details.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (assetId) The id of the asset for which the action is logged.
    * @param (string) (action) Name of the action that is logged. This must start with "CUSTOM_ACTION_", if it does not, this prefix will be added to the logged action name.
    * @param (array) ($additionalQueryParameters) Array of additional query parameters that are logged as details for the action.
    * @return (object) This call does not return a value, it only returns an http 200 status OK.
    */
    public function logUsage($sessionId, $assetId, $action, $additionalQueryParameters = array())
    {
        // Form createRelation parameters
        $logUsageParameters = array(
            'assetId'   => $assetId,
            'action'    => $action
        );

        // Add additional parameters
        $logUsageParameters = array_merge($logUsageParameters, $additionalQueryParameters);

        $response = Elvis::query($sessionId, 'logUsage', $logUsageParameters);

        return $response;
    }

    /**
    * Messages
    *
    * Retrieve message bundles from the Elvis server.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (array) (localChain) Array containing list of locales, the first supplied locale is leading. If a message is missing for a locale it will fall back to the next locale in the chain for that message.
    * @param (string) (ifModifiedSince) The date of the last requested cached messages, specified in milliseconds since the standard base time known as "the epoch", namely January 1, 1970, 00:00:00 GMT.
    * @param (string) (bundle) The bundle to return, can be either web or acm. The cmn bundle will always be returned combined with the requested bundle.
    * @return (object) Object containing all keys and messages.
    */
    public function messages($sessionId, $localeChain = null, $ifModifiedSince = null, $bundle = null)
    {
        // Form message parameters
        $messagesParameters = array(
            'localeChain'       => $localeChain,
            'ifModifiedSince'   => $ifModifiedSince,
            'bundle'            => $bundle
        );

        $response = Elvis::query($sessionId, 'messages', $messagesParameters);

        return $response;
    }

    /**
    * Checkout
    *
    * Checks out an asset from the system locking the file for other users.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (assetId) The Elvis id of the asset to be checked out.
    * @return (object) Object containing all keys and messages.
    */
    public function checkout($sessionId, $assetId)
    {
        // Form message parameters
        $checkoutParameters = array('assetId' => $assetId);

        $response = Elvis::query($sessionId, 'checkout', $checkoutParameters);

        return $response;
    }

    /**
    * Undocheckout
    *
    * Undo a checkout for a single asset
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (assetId) Elvis id of the asset that was checked out.
    * @return (object) Object containing all keys and messages.
    */
    public function undocheckout($sessionId, $assetId)
    {
        // Form message parameters
        $undocheckoutParamaters = array('assetId' => $assetId);

        $response = Elvis::query($sessionId, 'undocheckout', $undocheckoutParamaters);

        return $response;
    }

    /**
    * Zip download
    *
    * Download originals or previews as a ZIP file
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (filename) Filename of the zip file to be created
    * @param (string) (downloadKind) The type of the files that are included in the archive. Possible values are original or preview.
    * @param (array) (assetIds) Array containing the asset to be included in the Zip file 
    * @return (object) 
    */
    public function zip($sessionId, $filename, $downloadKind, $assetIds)
    {
        // Form zip parameters
        $zipParameters = array(
            'filename'      => $filename,
            'downloadKind'  => $downloadKind,
            'assetIds'      => implode(',', $assetIds),
        );

         // Do the query
        $response = Elvis::query($sessionId, 'zip', $zipParameters);

        return $response;
    }

    /**
    * REST call
    *
    * Performs the actual REST query
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (endpoint) Name of the actual REST API endpoint (login, search, create etc.)
    * @param (array) (parameters) All query parameters
    * @param (array) (metadata) Query parameters that will be converted to JSON array
    * @param (string) (filename) The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
    * @return (object) Query response or exception if something went wrong
    */
    public function query($sessionId = null, $endpoint, $parameters = null, $metadata = null, $filename = null)
    {
        // Form query URI
        $uri = $this->formQueryUrl($endpoint, $parameters, $metadata);

        // Create new Guzzle client
        $client = new \GuzzleHttp\Client();

        // Form Guzzle query depending on the endpaint
        switch ($endpoint) {
            // For login we dont set the cookie
            case 'login':
                $response = $client->get($uri);
                break;

            // For create we have store file contents and send it as variable Filedata
            case 'create':
                $response = $client->post($uri, ['headers' => ['Cookie' => 'JSESSIONID=' . $sessionId], 'body' => ['Filedata' => fopen($filename, 'r')]]);
                break;
            
            default:
                $response = $client->get($uri, ['headers' => ['Cookie' => 'JSESSIONID=' . $sessionId]]);
                break;
        }

        // Convert JSON response to StdObject
        $response_object = json_decode((string) $response->getBody());
        
        // Throw exceptions if necessary
        $this->checkResponse($response, $response_object);

        // Return the API JSON response as object
        return $response_object;
    }

    /**
    * Check the response for errors and throws necessary exceptions
    *
    * @param (object) (response) Guzzle HTTP response object
    * @param (string) (response_object) JSON encoded response
    * @return (bool) Empty response
    *
    */
    public function checkResponse($response, $response_object)
    {
         // Check if get 404
        if ($response->getStatusCode() == '404') {
            App::abort($response_object->code, 'The requested resource not found. Please check the api_endpoint_uri in the configuration.');
        }

        // For login, check if get error
        if (isset($response_object->loginSuccess) && $response_object->loginSuccess === false) {
            App::abort($response->getStatusCode(), $response_object->loginFaultMessage);
        }

        // Check if get an errorcode in the response
        if (isset($response_object->errorcode)) {
            App::abort($response_object->errorcode, 'Error: ' . $response_object->message);
        }

        return null;
    }

    /**
    * Form query URI
    *
    * Creates the URL with all the session id's, parameters etc.
    *
    * @param (string) (sessionId) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (endpoint) Name of the actual REST API endpoint (login, search, create etc.)
    * @param (array) (parameters) All query parameters
    * @param (array) (metadata) Query parameters that will be converted to JSON array
    * @return (string) The complete URL of the REST request
    */
    public function formQueryUrl($endpoint, $parameters, $metadata = null)
    {
        // Form basic URI
        $baseUrl = array();
        $baseUrl['baseUrl'] = Config::get('elvis::api_endpoint_uri');
        $baseUrl['endpoint'] = $endpoint;

        // Add filename to Zip download
        if($endpoint === 'zip')
        {
            $baseUrl['zipFilename'] = '/' . $parameters['filename'];
            unset($parameters['filename']);
            
            // Remove services, since zip download is at the root
            $baseUrl['baseUrl'] = str_replace('services/', '', $baseUrl['baseUrl']);
        }

        // Move assetId for checkout and undocheckout           
        if($endpoint === 'checkout' || $endpoint === 'undocheckout')
        {
            $baseUrl[$endpoint] = '/' . $parameters['assetId'];
            
            // Set parameters to null, since nothing else left
            $parameters = null;            
        }

        // Form query parameter
        $queryParameters = $this->formQueryParameters($parameters, $metadata);

        // Combine base url and query parameters
        $combinedUrl = array_merge($baseUrl, $queryParameters);
        
        // Form complete URI by imploding the array
        $uri = implode('', $combinedUrl);

        return $uri;
    }

    /**
    * Form query parameters
    *
    * Creates the query parameters
    *
    * @param (array) (parameters) All query parameters
    * @param (array) (metadata) Query parameters that will be converted to JSON array
    * @return (array) The query parameters to append
    */
    public function formQueryParameters($parameters, $metadata)
    {
        // Init array
        $query = array();

        // Add separator if either parameters or JSON encoded parameter 'metadata' is present and create array to store all parameters + possible metadata
        if (($parameters !== null || $metadata !== null)) {
            $query['parametersSeparator'] = '?';            
        }

        // Init query parameters array
        $queryParameters = array();

        // Add normal key=value parameters if needed, basically everything else except logout
        if ($parameters !== null) {
            $queryParameters = array_merge($queryParameters, $parameters);
        }

        // Add metadata='JSON encoded values'
        if ($metadata !== null) {
            $jsonMetadata = array('metadata' => json_encode($metadata));
            $queryParameters = array_merge($queryParameters, $jsonMetadata);
        }

        // Build query if necessary
        if (isset($queryParameters)) {
            $query['parameters'] = http_build_query($queryParameters);
        }

        return $query;
    }
}
