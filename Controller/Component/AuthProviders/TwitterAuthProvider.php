<?php

require_once('AbstractAuthProvider.php');

class TwitterAuthProvider extends AbstractAuthProvider {

	public function __construct() {
		parent::__construct('OAuth1');

		$this->key = Configure::read('ExtAuth.Provider.Twitter.key');
		$this->secret = Configure::read('ExtAuth.Provider.Twitter.secret');

		$this->requestTokenURL = 'https://api.twitter.com/oauth/request_token';

		$this->authenticateURL = 'https://api.twitter.com/oauth/authenticate';
		$this->authenticateParameters = array(
			'oauth_token' => '{REQUEST_TOKEN_KEY}'
		);

		$this->accessTokenURL = 'https://api.twitter.com/oauth/access_token';

		$this->profileURL = 'https://api.twitter.com/1.1/account/verify_credentials.json';
	}

	public function normalizeProfile($raw_profile) {
		$profile = json_decode($raw_profile, TRUE);

		// straight copy items
		$response = array_intersect_key(
			$profile,
			array_flip(array())
		);

		// mapped items
		$map = array(
			'lang'               => 'locale',
			'profile_image_url'  => 'picture',
			'screen_name'       => 'username'
		);
		foreach($map as $source => $dest) {
			if (isset($profile[$source])) $response[$dest] = $profile[$source];
		}

		// Compound items
		$names = explode(' ', $profile['name']);
		$response['given_name'] = $names[0];
		$response['family_name'] = (count($names)>1 ? end($names) : '');

		// fake OID as Twitter doesnt support OpenID
		$response['oid'] = 'http:/twitter.com/oid/' . $profile['id'];

		$response['raw'] = $raw_profile;
		$response['provider'] = 'Twitter';

		return array(
			'success'   => true,
			'data'      => $response
		);
	}
}
