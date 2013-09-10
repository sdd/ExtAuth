<?php

require_once('OAuthAuthMechanism.php');

class OAuth1AuthMechanism extends OAuthAuthMechanism {

	public function login($provider, $callbackURL) {
		$requestToken = $this->getRequestToken($provider, $callbackURL);

		if ($requestToken['success']) {

			$query = str_replace(
				array('{REQUEST_TOKEN_KEY}', '{REQUEST_TOKEN_SECRET}'),
				array($requestToken['data']->key, $requestToken['data']->secret),
				$provider->authenticateParameters
			);

			return array(
				'success'       => true,
				'requestToken'  => $requestToken['data'],
				'redirectURL'   => $provider->authenticateURL . '?' . http_build_query($query)
			);
		} else {
			return $requestToken;
		}
	}

	public function getRequestToken($provider, $callbackURL) {
		return $this->_getToken(
			$provider,
			'request',
			array(
				'oauth_callback' => $callbackURL
			)
		);
	}

	public function getAccessToken($provider, $requestToken, $callbackURL = null) {
		$parsed_params = Eher\OAuth\Util::parse_parameters($_SERVER['QUERY_STRING']);

		return $this->_getToken(
			$provider,
			'access',
			array(
				'oauth_verifier' => $parsed_params['oauth_verifier']
			),
			$requestToken
		);
	}

	protected function _getToken($provider, $tokenType, $parameters, $token = null) {

		$response = $this->apiRequest(
			$provider,
			$provider->{$tokenType . 'TokenURL'},
			$token,
			$provider->{'get' . ucfirst($tokenType) . 'TokenParameters'}($parameters),
			$provider->{$tokenType . 'TokenRequestMethod'}
		);

		if ($response['success']) {

			$result = array();
			parse_str($response['data'], $result);

			if (isset($result['oauth_token']) && isset($result['oauth_token_secret'])) {
				return array(
					'success' => true,
					'data'   => new Eher\OAuth\Token($result['oauth_token'], $result['oauth_token_secret'])
				);
			} else {
				return array(
					'success'   => false,
					'message'   => 'Could not parse the response to the token request',
					'data'      => $result
				);
			}
		} else {
			$response['message'] = $tokenType . ' Token ' . $response['message'];
			return $response;
		}
	}

	public function apiRequest($provider, $url, $token = null, $parameters = array(), $method = 'GET', $headers = array()) {
		//TODO: headers

		$consumer = new Eher\OAuth\Consumer($provider->key, $provider->secret);

		$request = Eher\OAuth\Request::from_consumer_and_token(
			$consumer,
			$token,
			$method,
			$url,
			$parameters
		);

		$request->sign_request(new Eher\OAuth\HmacSha1(), $consumer, $token);

		switch ($method) {
			case 'POST':
				$response = $this->_doPost($url, $request->to_postdata());
				break;

			case 'GET':
			default:
				$response = $this->_doGet($request->to_url());
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
