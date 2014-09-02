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
     public static function login()
      {
        // Form login parameters
        $login_parameters = array(
            'username'    =>    Config::get('elvis::username'),
              'password'    =>    Config::get('elvis::password')
        );

          $response = Elvis::query(null, 'login', $login_parameters);

        return $response->body->sessionId;
    }

    /**
    * Logout
    *
    * Logouts from Elvis with the given session id
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @return (bool) (logoutSuccess) True if logout was succesfull
    */
     public static function logout($session_id)
      {
        // Call logout REST API
        $response = Elvis::query($session_id, 'logout');

        return $response->body->logoutSuccess;
    }

    /**
     * Search
     *
     * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
     * @param (string) (q) Actual Lucene query, you can find more details in https://elvis.tenderapp.com/kb/technical/query-syntax
     * @param (int) (start) First hit to be returned. Starting at 0 for the first hit. Used to skip hits to return 'paged' results. Default is 0.
     * @param (int) (num) Number of hits to return. Specify 0 to return no hits, this can be useful if you only want to fetch facets data. Default is 50.
     * @param (string) (sort) The sort order of returned hits. Comma-delimited list of fields to sort on. Read more at https://elvis.tenderapp.com/kb/api/rest-search
     * @param (string) (metadataToReturn) Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://elvis.tenderapp.com/kb/api/rest-search
     * @param (bool) (appendRequestSecret) When set to true will append an encrypted code to the thumbnail, preview and original URLs.
     * @return (object) List of search results
     */
    public static function search($session_id, $q, $start = 0, $num = 50, $sort = 'assetCreated-desc', $metadataToReturn = 'all', $appendRequestSecret = false)
    {
        // Form search parameters
        $search_parameters = array(
            'q'                        => $q,
              'start'                    => $start,
              'num'                    => $num,
              'sort'                    => $sort,
              'metadataToReturn'        => $metadataToReturn,
              'appendRequestSecret'    => $appendRequestSecret
        );

        // Call the search REST API
        $response = Elvis::query($session_id, 'search', $search_parameters);

        return $response->body;
    }

    /**
    * Browse
    *
    * This call is designed to allow you to browse folders and show their subfolders and collections, similar to how folder browsing works in the Elvis desktop client.
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (path) The path to the folder in Elvis you want to list.
    * @param (string) (fromRoot) Allows returning multiple levels of folders with their children. When specified, this path is listed, and all folders below it up to the 'path' will have their children returned as well.
    * @param (bool) (includeFolders) Indicates if folders should be returned. Optional. Default is true.
    * @param (bool) (includeAsset) Indicates if files should be returned. Optional. Default is true, but filtered to only include 'container' assets.
    * @param (string) (includeExtensions) A comma separated list of file extensions to be returned. Specify 'all' to return all file types.
    * @return (object) (results) An array of folders and assets.
    */
     public static function browse($session_id, $path, $fromRoot = null, $includeFolders = true, $includeAsset = true, $includeExtensions = '.collection, .dossier, .task')
      {
        // Form browse parameters
        $browse_parameters = array(
            'path'                => $path,
            'fromRoot'            => $fromRoot,
            'includeFolders'    => $includeFolders,
            'includeAsset'      => $includeAsset,
            'includeExtensions'    => $includeExtensions
        );

        // Call browse REST API
        $response = Elvis::query($session_id, 'browse', $browse_parameters);

          return $response->body;
    }

    /**
    * Profile
    *
    * Retrieve details about the user authenticated in the current browser session.
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @return (object) Profile attached to the session
    */
     public static function profile($session_id)
      {
        // Call profile REST API
        $response = Elvis::query($session_id, 'profile');

        return $response->body;
    }

    /**
    * Create
    *
    * Upload and create an asset.
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (filename) The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
    * @param (array) (metadata) Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
    * @return (object) Information about the newly created asset
    */
     public static function create($session_id, $filename, $metadata)
      {

        $response = Elvis::query($session_id, 'create', null, $metadata, $filename);

        return $response->body;
    }

    /**
    * Update
    *
    * Upload and create an asset.
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (id) Elvis asset id to be updated
    * @param (string) (filename) The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
    * @param (array) (metadata) Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
    * @return (object) Elvis returns something strange, TODO investigate it
    */
     public static function update($session_id, $id, $filename, $metadata)
      {
        // Form update parameters
        $update_parameters = array(
            'id' => $id,
        );

        $response = Elvis::query($session_id, 'update', $update_parameters, $metadata, $filename);

        return $response->body;
    }

    /**
    * Updatebulk
    *
    * This call updates the metadata of multiple existing assets in Elvis.
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (q) A query matching the assets that should be updated
    * @param (array) (metadata) Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
    * @param (bool) (async) When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.
    * @return (object) Either processedCount or processId depending if async is true or false
    */
     public static function updatebulk($session_id, $query, $metadata, $async = false)
     {
         // Form updatebulk parameters
        $updatebulk_parameters = array(
            'q'        => $query,
            'async'    => $async
        );

         // Do the query
        $response = Elvis::query($session_id, 'updatebulk', $updatebulk_parameters, $metadata);

        return $response->body;
    }

    /**
    * Move / rename
    *
    * Move or rename a folder or a single asset.
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (source) Either a folderPath or assetPath of the folder or asset to be moved or renamed.
    * @param (string) (target) The folderPath or assetPath to which the folder or asset should be moved or renamed. If the parent folder is the same as in the source path, the asset will be renamed, otherwise it will be moved.)
    * @param (string) (folderReplacePolicy) Policy used when destination folder already exists. Aither AUTO_RENAME (default), MERGE or THROW_EXCEPTION.
    * @param (string) (fileReplacePolicy) Policy used when destination asset already exists. Either AUTO_RENAME (default), OVERWRITE, OVERWRITE_IF_NEWER, REMOVE_SOURCE, THROW_EXCEPTION or DO_NOTHING
    * @param (string) (filterQuery) When specified, only source assets that match this query will be moved.
    * @param (bool) (flattenFolders) When set to true will move all files from source subfolders to directly below the target folder. This will 'flatten' any subfolder structure.
    * @param (bool) (async) When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.
    * @return (object) Either processedCount or processId depending if async is true or false
    */
     public static function move($session_id, $source, $target, $folderReplacePolicy = 'AUTO_RENAME', $fileReplacePolicy = 'AUTO_RENAME', $filterQuery = '*:*', $flattenFolders = false, $async = false)
     {
         // Form move parameters
        $move_parameters = array(
            'source'                => $source,
            'target'                => $target,
            'folderReplacePolicy'   => $folderReplacePolicy,
            'fileReplacePolicy'     => $fileReplacePolicy,
            'filterQuery'           => $filterQuery,
            'flattenFolders'        => $flattenFolders,
            'async'                 => $async
        );

         // Do the query
        $response = Elvis::query($session_id, 'move', $move_parameters);

        return $response->body;
     }

    /**
    * REST call
    *
    * Performs the actual REST query
    *
    * @param (string) (session_id) Session ID returned by the login function. This is used for further queries towards Elvis
    * @param (string) (endpoint) Name of the actual REST API endpoint (login, search, create etc.)
    * @param (array) (parameters) All query parameters
    * @param (array) (metadata) Query parameters that will be converted to JSON array
    * @param (string) (filename) The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
    * @return (object) Query response or exception if something went wrong
    */
    public static function query($session_id = null, $endpoint, $parameters = null, $metadata = null, $filename = null)
    {
        // Form basic URI
        $uri_parts = array();
        $uri_parts['base_url'] = Config::get('elvis::api_endpoint_uri');
        $uri_parts['method'] = $endpoint;

        // Add session if needed, basically everything else except login
        if ($session_id !== null) {
            $uri_parts['jsessionid'] = ';jsessionid=' . $session_id;
        }

        // Add separator if either parameters or JSON encoded parameter 'metadata' is present and create array to store all parameters + possible metadata
        if ($parameters !== null || $metadata !== null) {
            $uri_parts['parameters_separator'] = '?';
            $query_parameters = array();
        }

        // Add normal key=value parameters if needed, basically everything else except logout
        if ($parameters !== null) {
            // In case we have boolean parameters, we have to type cast those to strings.
            foreach ($parameters as $key => $value) {
                if (is_bool($value)) {
                    $parameters[$key] = ($value) ? 'true' : 'false';
                }
            }

            $query_parameters = array_merge($query_parameters, $parameters);
        }

        // Add metadata='JSON encoded values'
        if ($metadata !== null) {
            $json_metadata = array('metadata' => json_encode($metadata));
            $query_parameters = array_merge($query_parameters, $json_metadata);
        }

        // Build query if necessary
        if (isset($query_parameters)) {
            $uri_parts['parameters'] = http_build_query($query_parameters);
        }

        // Form complete URI by imploding the array
        $uri = implode($uri_parts);

        // Call REST API
        if ($filename !== null) {
            // If filename is given, we have to attach it (create method)
            $response = \Httpful\Request::post($uri)->attach(array('Filedata' => $filename))->send();
        } else {
            $response = \Httpful\Request::get($uri)->send();
        }

        // Check if get 404
        if ($response->code == 404) {
            App::abort($response->code, 'The requested resource not found. Please check the api_endpoint_uri in the configuration.');
        }

        // For login, check if get error
        if (isset($response->body->loginSuccess) && $response->body->loginSuccess === false) {
            App::abort($response->code, $response->body->loginFaultMessage);
        }

        // Check if get an errorcode in the response
        if (isset($response->body->errorcode)) {
            App::abort($response->body->errorcode, 'Error: ' . $response->body->message);
        }

        // Return the API JSON response as object
        return $response;
    }
}
