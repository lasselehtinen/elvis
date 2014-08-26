<?php namespace LasseLehtinen\Elvis;
 
 // Import classes to use the classic "Config::get()" approach and App for throwing exceptions
 use Config;
 use App;

class Elvis {
 
	/**
	* login
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
			'q'						=>	$q,
		  	'start'					=> $start,
		  	'num'					=> $num,
		  	'sort'					=> $sort,
		  	'metadataToReturn' 		=> $metadataToReturn,
		  	'appendRequestSecret'	=> $appendRequestSecret
		);

		print_r($search_parameters);
		
		// Call login REST API
		$uri = Config::get('elvis::api_endpoint_uri') . 'search' . ";jsessionid=" . $session_id . '?' . http_build_query($search_parameters) ;
		$response = \Httpful\Request::get($uri)->send();
		var_dump($response->body);
	}
 
}