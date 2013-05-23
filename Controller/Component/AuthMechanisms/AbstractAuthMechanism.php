<?php

abstract class AbstractAuthMechanism {

	public $supportsAPI = false;

	abstract public function login($providerName, $callbackURL);

	public function loginCallback($providerName, $token, $callbackURL) {
		throw new BadMethodCallException('This AuthMechanism does not have a login callback');
	}

	public function apiRequest($provider, $url, $token = null, $parameters = array(), $method = 'GET', $headers = array()) {
		throw new BadMethodCallException('This AuthMechanism has not implemented API Requests');
	}

	protected function _doGet($url) {
		$socket = new HttpSocket();
		$result = $socket->get($url);

		return $result;
	}

	protected function _doPost($url, $data) {
		$socket = new HttpSocket();
		$result = $socket->post($url, $data);

		return $result;
	}
}
