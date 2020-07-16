<?php
	require_once( 'defines.php' );

	Class instagram_basic_display_api {
		private $_appId = INSTAGRAM_APP_ID;
		private $_appSecret = INSTAGRAM_APP_SECRET;
		private $_redirectUrl = INSTAGRAM_APP_REDIRECT_URI;
		private $_instagramTokenStore = INSTAGRAM_TOKEN_STORE;
		private $_getCode = '';
		private $_apiBaseUrl = 'https://api.instagram.com/';
		private $_graphBaseUrl = 'https://graph.instagram.com/';
		private $_graphFbBaseUrl = 'https://graph.facebook.com/v7.0/';
		private $_userAccessToken = '';
		private $_userAccessTokenExpires = '';
		private $_instagramBusinessAccount = '';
		private $_username = '';

		public $authorizationUrl = '';
		public $hasUserAccessToken = false;
		public $userId = '';
		public $_feed = null;


		function __construct( $params ) {
			// save instagram code
			$this->_getCode = $params['get_code'];
			$this->_username = $params['username'];
			// get an access token
			$this->_setUserInstagramAccessToken( $params );

			// get authorization url
			$this->_setAuthorizationUrl();
		}

		public function getUserAccessToken() {
			return $this->_userAccessToken;
		}

		public function getUserAccessTokenExpires() {
			return $this->_userAccessTokenExpires;
		}

		private function _setAuthorizationUrl() {
			$getVars = array( 
				'app_id' => $this->_appId,
				'redirect_uri' => $this->_redirectUrl,
				'scope' => 'user_profile,user_media',
				'response_type' => 'code'
			);

			// create url
			$this->authorizationUrl = $this->_apiBaseUrl . 'oauth/authorize?' . http_build_query( $getVars );
		}

		private function _setUserInstagramAccessToken( $params ) {
			// if ( isset($params['access_token']) ) { // we have an access token
			// 	$this->_userAccessToken = $params['access_token'];
			// 	$this->hasUserAccessToken = true;
			// 	$this->userId = $params['user_id'];
			// } elseif ( $params['get_code'] ) { // try and get an access token
			// 	$userAccessTokenResponse = $this->_getUserAccessToken();
			// 	$this->_userAccessToken = $userAccessTokenResponse['access_token'];
			// 	$this->hasUserAccessToken = true;
			// 	$this->userId = $userAccessTokenResponse['user_id'];

			// 	// get long lived access token
			// 	$longLivedAccessTokenResponse = $this->_getLongLivedUserAccessToken();
			// 	$this->_userAccessToken = $longLivedAccessTokenResponse['access_token'];
			// 	$this->_userAccessTokenExpires = $longLivedAccessTokenResponse['expires_in'];
			// }
			$infos = $this->_getStore();
			$this->_userAccessToken = $infos['access_token'];
			$longLivedAccessTokenResponse = $this->_getLongLivedUserAccessToken();
			$this->_setStore($longLivedAccessTokenResponse);
			$this->_userAccessToken = $longLivedAccessTokenResponse['access_token'];
			if(isset($longLivedAccessTokenResponse['expires_in'])) {
				$this->_userAccessTokenExpires = $longLivedAccessTokenResponse['expires_in'];
			} else {
				$this->_userAccessTokenExpires = "NC";
			}
			$accounts = $this->_getMyAccounts();
			$this->_setInstagramBusinessAccount($accounts);
			// echo $this->_instagramBusinessAccount;die();
			$this->_feed = $this->_getUserFeed();
		}

		private function _getUserAccessToken() {
			$params = array(
				'endpoint_url' => $this->_apiBaseUrl . 'oauth/access_token',
				'type' => 'POST',
				'url_params' => array(
					'app_id' => $this->_appId,
					'app_secret' => $this->_appSecret,
					'grant_type' => 'authorization_code',
					'redirect_uri' => $this->_redirectUrl,
					'code' => $this->_getCode
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		private function _getLongLivedUserAccessToken() {
			$params = array(
				'endpoint_url' => $this->_graphFbBaseUrl . 'oauth/access_token',
				'type' => 'GET',
				'url_params' => array(
					'client_id' => $this->_appId,
					'client_secret' => $this->_appSecret,
					'grant_type' => 'fb_exchange_token',
					'fb_exchange_token' => $this->_userAccessToken
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		public function getUser() {
			$params = array(
				'endpoint_url' => $this->_graphBaseUrl . 'me',
				'type' => 'GET',
				'url_params' => array(
					'fields' => 'id,username,media_count,account_type',
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		public function _getMyAccounts()
		{
			$params = array(
				'endpoint_url' => $this->_graphFbBaseUrl . 'me/accounts',
				'type' => 'GET',
				'url_params' => array(
					'fields' => 'instagram_business_account,access_token',
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		private function _setInstagramBusinessAccount($response)
		{
			for ($i=0; $i < count($response['data']); $i++) { 
				$account = $response['data'][$i];
				if(isset($account["instagram_business_account"])) {
					$this->_instagramBusinessAccount = $account["instagram_business_account"]['id'];
				}
			}
		}

		public function _getUserFeed()
		{
			if($this->_instagramBusinessAccount != "") {
				$params = array(
					'endpoint_url' => $this->_graphFbBaseUrl . $this->_instagramBusinessAccount,
					'type' => 'GET',
					'url_params' => array(
						'fields' => 'business_discovery.username('.$this->_username.'){followers_count,media_count,media{comments_count,like_count,media_url,caption,timestamp}}',
					)
				);
	
				$response = $this->_makeApiCall( $params );
				return $response;
			} else {
				return null;
			}
		}

		public function getPageTest() {
			$params = array(
				'endpoint_url' => $this->_graphBaseUrl .  $this->userId.'me',
				'type' => 'GET',
				'url_params' => array(
					'fields' => 'id,username,media_count,account_type',
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		public function getUsersMedia() {
			$params = array(
				'endpoint_url' => $this->_graphBaseUrl . $this->userId,
				'type' => 'GET',
				'url_params' => array(
					'fields' => 'business_discovery.username(skysignage)',
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		public function getPaging( $pagingEndpoint ) {
			$params = array(
				'endpoint_url' => $pagingEndpoint,
				'type' => 'GET',
				'url_params' => array(
					'paging' => true
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		public function getMedia( $mediaId ) {
			$params = array(
				'endpoint_url' => $this->_graphBaseUrl . $mediaId,
				'type' => 'GET',
				'url_params' => array(
					'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username'
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		public function getMediaChildren( $mediaId ) {
			$params = array(
				'endpoint_url' => $this->_graphBaseUrl . $mediaId . '/children',
				'type' => 'GET',
				'url_params' => array(
					'fields' => 'id,media_type,media_url,permalink,thumbnail_url,timestamp,username'
				)
			);

			$response = $this->_makeApiCall( $params );
			return $response;
		}

		private function _makeApiCall( $params ) {
			$ch = curl_init();

			$endpoint = $params['endpoint_url'];

			if ( 'POST' == $params['type'] ) { // post request
				curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params['url_params'] ) );
				curl_setopt( $ch, CURLOPT_POST, 1 );
			} elseif ( 'GET' == $params['type'] && ! isset($params['url_params']['paging']) ) { // get request
				$params['url_params']['access_token'] = $this->_userAccessToken;

				//add params to endpoint
				$endpoint .= '?' . http_build_query( $params['url_params'] );
			}

			// general curl options
			curl_setopt( $ch, CURLOPT_URL, $endpoint );

			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			$response = curl_exec( $ch );

			curl_close( $ch );

			$responseArray = json_decode( $response, true );

			if ( isset( $responseArray['error_type'] ) ) {
				var_dump( $responseArray );
				die();
			} else {
				return $responseArray;
			}
		}

		public function _getStore() {
			$string = file_get_contents($this->_instagramTokenStore);
			$infos = json_decode($string, true);
			return $infos;
		}

		private function _setStore($response)
		{
			$fp = fopen($this->_instagramTokenStore, 'w');
			fwrite($fp, json_encode($response));
			fclose($fp);
			return true;
		}
	}