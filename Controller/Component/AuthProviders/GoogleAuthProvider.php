<?php

require_once('AbstractAuthProvider.php');

class GoogleAuthProvider extends AbstractAuthProvider {

	public function __construct() {
		parent::__construct('OAuth2');

		$this->key = Configure::read('ExtAuth.Provider.Google.key');
		$this->secret = Configure::read('ExtAuth.Provider.Google.secret');

		$this->AuthDialogURL = 'https://accounts.google.com/o/oauth2/auth';
		$this->AuthDialogParameters = array(
			'client_id'     => $this->key,
			'response_type' => 'code',
			'scope'         => 'openid profile',
			'redirect_uri'  => '{CALLBACK_URL}',
			//'state'         => '{STATE}',
		);

		$this->accessTokenURL = 'https://accounts.google.com/o/oauth2/token';
		$this->accessTokenParameters = array(
			'client_id'     => $this->key,
			'client_secret' => $this->secret,
			'redirect_uri'  => '{CALLBACK_URL}',
			'grant_type'    => 'authorization_code'
		);

		$this->profileURL = 'https://www.googleapis.com/oauth2/v1/userinfo';
		$this->profileParameters = array('alt' => 'json');
	}

	public function getAccessTokenParameters($params = null) {
		$parameters = parent::getAccessTokenParameters($params);
		$parameters['code'] = $_GET['code'];
		return $parameters;
	}

	public function normalizeProfile($raw_profile) {
		$profile = json_decode($raw_profile, TRUE);

		// straight copy items
		$response = array_intersect_key(
			$profile,
			array_flip(array('email', 'given_name', 'family_name', 'picture', 'gender', 'locale'))
		);

		// mapped items
		$map = array(
			'link'      => 'oid',
			'birthday'  => 'dob',
			'name'      => 'username'
		);
		foreach($map as $source => $dest) {
			if (isset($profile[$source])) $response[$dest] = $profile[$source];
		}

		$response['raw'] = $raw_profile;
		$response['provider'] = 'Google';

		return array(
			'success'   => true,
			'data'      => $response
		);
	}
}
