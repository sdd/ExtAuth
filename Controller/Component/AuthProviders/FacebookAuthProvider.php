<?php

require_once('AbstractAuthProvider.php');

class FacebookAuthProvider extends AbstractAuthProvider {

	public function __construct() {
		parent::__construct('OAuth2');

		$this->key = Configure::read('ExtAuth.Provider.Facebook.key');
		$this->secret = Configure::read('ExtAuth.Provider.Facebook.secret');

		$this->AuthDialogURL            = 'https://www.facebook.com/dialog/oauth/';
		$this->AuthDialogParameters = array(
			'client_id'         => $this->key,
			'redirect_uri'      => '{CALLBACK_URL}',
			//'state'           => '{STATE}',
		);

		$this->accessTokenURL           = 'https://graph.facebook.com/oauth/access_token';
		$this->accessTokenRequestMethod = 'GET';
		$this->accessTokenParameters = array(
			'client_id'     => $this->key,
			'client_secret' => $this->secret,
			'redirect_uri'  => '{CALLBACK_URL}',
		);

		$this->profileURL = 'https://graph.facebook.com/me';
	}

	public function getAccessTokenParameters($params = null) {
		$parameters = parent::getAccessTokenParameters($params);
		if (isset($_GET['code'])) $parameters['code'] = $_GET['code'];
		return $parameters;
	}

	public function normalizeProfile($raw_profile) {
		$profile = json_decode($raw_profile, TRUE);

		// straight copy items
		$response = array_intersect_key(
			$profile,
			array_flip(array('locale', 'gender', 'username'))
		);

		// mapped items
		$map = array(
			'first_name'        => 'given_name',
			'last_name'         => 'family_name',
			'link'              => 'oid',
		);
		// if no username, map name to username
		if (!isset($response['username'])) $map['name'] = 'username';

		// do mapping
		foreach($map as $source => $dest) {
			if (isset($profile[$source])) {
				$response[$dest] = $profile[$source];
			}
		}

		// special cases
		$response['picture'] = str_replace('www.facebook.com', 'graph.facebook.com', $profile['link']) . '/picture?type=large';

		$response['raw'] = $raw_profile;
		$response['provider'] = 'Facebook';

		return array(
			'success'   => true,
			'data'      => $response
		);
	}
}
