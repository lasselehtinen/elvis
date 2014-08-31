<?php namespace LasseLehtinen\Elvis;
 
 // Import classes to use the classic "Config::get()" approach and App for throwing exceptions
 use Config;
 use App;

class Elvis {
 
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
			'username'	=>	Config::get('elvis::username'),
		  	'password'	=>	Config::get('elvis::password')
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
			'q'						=> $q,
		  	'start'					=> $start,
		  	'num'					=> $num,
		  	'sort'					=> $sort,
		  	'metadataToReturn' 		=> $metadataToReturn,
		  	'appendRequestSecret'	=> $appendRequestSecret
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
			'path'				=> $path,
			'fromRoot'			=> $fromRoot,
			'includeFolders'	=> $includeFolders,
			'includeAsset'		=> $includeAsset,
			'includeExtensions'	=> $includeExtensions
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
	* @return (object) Profile attached to the session
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
	* @return (object) Profile attached to the session
	*/
 	public static function update($session_id, $id, $filename, $metadata)
  	{    
		// Form update parameters
		$update_parameters = array(
			'id'				=> $id,
		);

		$response = Elvis::query($session_id, 'update', $update_parameters, $metadata, $filename);

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
		if($session_id !== null)
		{
			$uri_parts['jsessionid'] = ';jsessionid=' . $session_id;
		}

		// Add separator if either parameters or JSON encoded parameter 'metadata' is present and create array to store all parameters + possible metadata
		if($parameters !== null || $metadata !== null)
		{
			$uri_parts['parameters_separator'] = '?';
			$query_parameters = array();
		}

		// Add normal key=value parameters if needed, basically everything else except logout
		if($parameters !== null)
		{
			$query_parameters = array_merge($query_parameters, $parameters);
		}

		// Add metadata='JSON encoded values' 
		if($metadata !== null)
		{
			$json_metadata = array('metadata' => json_encode($metadata));
			$query_parameters = array_merge($query_parameters, $json_metadata);
		}

		// Build query if necessary
		if(isset($query_parameters))
		{
			$uri_parts['parameters'] = http_build_query($query_parameters);
		}

		// Form complete URI by imploding the array
		$uri = implode($uri_parts);

		// Call REST API 
		if($filename !== null)	// If filename is given, we have to attach it (create method)
		{
			$response = \Httpful\Request::post($uri)->attach(array('Filedata' => $filename))->send();
		}
		else
		{
			$response = \Httpful\Request::get($uri)->send();			
		}

		// Check if get 404
		if($response->code == 404)
		{
			App::abort($response->code, 'The requested resource not found. Please check the api_endpoint_uri in the configuration.');
		}

		// For login, check if get error
		if(isset($response->body->loginSuccess) && $response->body->loginSuccess === false)
		{
			App::abort($response->code, $response->body->loginFaultMessage);
		}

		// Check if get an errorcode in the response
		if(isset($response->body->errorcode))
		{
			App::abort($response->body->errorcode, 'Error: ' . $response->body->message);
		}

		// Return the API JSON response as object
		return $response;
	}
}