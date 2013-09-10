<?php

require_once('OAuthAuthMechanism.php');

class OAuth2AuthMechanism extends OAuthAuthMechanism {

	public function login($provider, $callbackURL) {

		$query = $provider->AuthDialogParameters;

		// resolve substitutions
		array_walk($query, function(&$value, $key, $substitutions) {
			$value = str_replace(
				array_keys($substitutions),
				array_values($substitutions),
				$value
			);
		}, array(
			'{STATE}'   => null,
			'{CALLBACK_URL}'    => $callbackURL
		));

		return array(
			'success'       => true,
			'requestToken'  => null,
			'redirectURL'   => $provider->AuthDialogURL . '?' . http_build_query($query)
		);
	}

	public function getAccessToken($provider, $requestToken = null, $callbackURL = null) {

		$query = $provider->getAccessTokenParameters();

		// resolve substitutions
		array_walk($query, function(&$value, $key, $substitutions) {
			$value = str_replace(
				array_keys($substitutions),
				array_values($substitutions),
				$value
			);
		}, array(
			'{CALLBACK_URL}'    => $callbackURL
		));

		$response = $this->apiRequest(
			null, // provider not required for OAuth2 apiRequest
			$provider->accessTokenURL,
			null,
			$query,
			$provider->accessTokenRequestMethod
		);

		if ($response['success']) {
			$frags = json_decode($response['data'], true);
			if (!$frags) {
				parse_str($response['data'], $frags);
			}

			if (isset($frags['access_token'])) {
				return array(
					'success'   => true,
					'data'      => $frags['access_token']
				);
			} else {
				return array(
					'success'   => false,
					'message'   => 'Unable to parse Access Token response',
					'data'      => $response['data']
				);
			}
		} else {
			return $response;
		}
	}

	public function apiRequest($provider = null, $url, $accessToken = null, $parameters = array(), $method = 'GET', $headers = array()) {
		//TODO: headers

		// merge accessToken into parameters, if present
		if ($accessToken) {
			$parameters['access_token'] = $accessToken;
		}

		switch ($method) {
			case 'POST':
				$response = $this->_doPost($url, $parameters);
				break;

			case 'GET';
			default:
				$response = $this->_doGet($url . '?' . http_build_query($parameters));
		}

		if ($response->code === "200") {
			return array(
				'success'   => true,
				'data'    => $response->body
			);
		} else {
			return array(
				'success'   => false,
				'message'   => 'API Request HTTP Error: status ' . $response->code,
				'data'      => $response
			);
		}
	}
}
