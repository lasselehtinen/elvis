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
  		
		// Call login REST API
		$uri = Config::get('elvis::api_endpoint_uri') . 'login?' . http_build_query($login_parameters);
		$response = \Httpful\Request::get($uri)->send();

		// Check that URL does not return 404
		if($response->code === 404)
		{
			App::abort(404, 'API endpoint URL returned code 404 (Not found): ' . Config::get('elvis::api_endpoint_uri'));
		}

		// Check if login was false
		if($response->body->loginSuccess === false)
		{
			App::abort(403, 'Login failed: ' . $response->body->loginFaultMessage);
		}

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
		$uri = Config::get('elvis::api_endpoint_uri') . 'logout;jsessionid=' . $session_id;
		$response = \Httpful\Request::get($uri)->send();

		// Check we get an error code
		if(isset($response->body->errorcode))
		{
			App::abort($response->body->errorcode, 'Error: ' . $response->body->message);
		}

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
	 * @return (array) List of search results	 
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

		// Call login REST API
		$uri = Config::get('elvis::api_endpoint_uri') . 'search;jsessionid=' . $session_id . '?' . http_build_query($search_parameters);
		$response = \Httpful\Request::get($uri)->send();

		// Check we get an error code
		if(isset($response->body->errorcode))
		{
			App::abort($response->body->errorcode, 'Error: ' . $response->body->message);
		}
		
		return $response->body;
	}

	/**
	* Browse
	*
	* This call is designed to allow you to browse folders and show their subfolders and collections, similar to how folder browsing works in the Elvis desktop client.
	*
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
		$uri = Config::get('elvis::api_endpoint_uri') . 'browse;jsessionid=' . $session_id . '?' . http_build_query($browse_parameters);
		$response = \Httpful\Request::get($uri)->send();
  		
  		return $response->body;
	}
}