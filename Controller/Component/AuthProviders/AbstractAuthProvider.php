<?php

abstract class AbstractAuthProvider {

	public function __construct($mechanism) {
		$this->mechanism = $mechanism;

		$this->requestTokenRequestMethod        = 'GET';
		$this->accessTokenRequestMethod         = 'POST';
		$this->profileRequestMethod             = 'GET';

		$this->requestTokenParameters           = array();
		$this->accessTokenParameters            = array();
		$this->profileParameters                = array();

		$this->requestTokenRequestHeaders       = array();
		$this->authenticateParameters = array(
			'oauth_token' => '{REQUEST_TOKEN_KEY}'
		);
		$this->accessTokenRequestHeaders        = array();
		$this->profileRequestHeaders            = array();

	}

	public function getRequestTokenParameters($params = null) {
		if ($params === null) {
			$parameters = $this->requestTokenParameters;
		} else {
			$parameters = array_merge($this->requestTokenParameters, $params);
		}
		return $parameters;
	}

	public function getAuthDialogParameters($params = null) {
		if ($params === null) {
			$parameters = $this->accessTokenParameters;
		} else {
			$parameters = array_merge($this->accessTokenParameters, $params);
		}
		return $parameters;
	}

	public function getAccessTokenParameters($params = null) {
		if ($params === null) {
			$parameters = $this->accessTokenParameters;
		} else {
			$parameters = array_merge($this->accessTokenParameters, $params);
		}
		return $parameters;
	}

	abstract public function normalizeProfile($raw_profile);
}
