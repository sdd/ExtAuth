<?php
/**
 * A CakePHP component to handle federated authentication, via an extensible
 * paradigm that supports OAuth 1.0, 1.0a, 2.0, and OpenID.
 *
 * Uses the OAuth library from http://oauth.googlecode.com
 *
 * Copyright © 2013 Scott Donnelly ( http://scott.donnel.ly )
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 */

App::uses('HttpSocket', 'Network/Http');

class ExtAuthComponent extends Component {

	var $components = array('Session');
	var $providerName = null;
	var $provider = null;
	var $mechanism = null;

	public function __construct(ComponentCollection $collection, array $settings = array()) {
		parent::__construct($collection, $settings);

		$this->settings = array(

			// Default Paths
			'AuthMechanismPath' =>  array(
				APP . 'Controller' . DS . 'Component' . DS . 'AuthMechanisms' . DS,
				dirname(__FILE__) . DS . 'AuthMechanisms' . DS
			),
			'AuthProviderPath' =>  array(
				APP . DS . 'Controller' . DS . 'Component' . DS . 'AuthProviders' . DS,
				dirname(__FILE__) . DS . 'AuthProviders' . DS
			)
		);

		foreach($this->settings as $setting => $value) {
			if (isset($settings[$setting])) {
				array_unshift($this->settings[$setting], $settings[$setting]);
				unset($settings[$setting]);
			}
		}

		$this->settings = array_merge($this->settings, array(

			// Default Settings
			'callbackURL'                   => Router::url('/', true).'/auth_callback/{PROVIDER}',
			'sessionVariableRequestToken'   => 'request_token',
			'sessionVariableAccessToken'    => 'access_token',

		), $settings);
	}

	public function login($providerName, $callbackURL = null) {
		$this->providerName = $providerName;
		$this->_setupPlugins();

		$callbackURL = $this->_generateCallbackURL($callbackURL);

		$result = $this->mechanism->login($this->provider, $callbackURL);
		if (isset($result['requestToken'])) {
			$this->Session->write($this->settings['sessionVariableRequestToken'], $result['requestToken']);
		}

		return $result;
	}

	public function loginCallback($providerName, $requestToken = null, $callbackURL = null) {
		$this->providerName = $providerName;
		$this->_setupPlugins();

		if (!$requestToken) {
			$requestToken = $this->Session->read($this->settings['sessionVariableRequestToken']);
		}

		$callbackURL = $this->_generateCallbackURL($callbackURL);

		return $this->mechanism->loginCallback(
			$this->provider,
			$requestToken, $callbackURL
		);
	}

	public function apiRequest($providerName, $url, $params = array(), $accessToken = null, $method = null, $headers = array()) {
		$this->providerName = $providerName;
		$this->_setupPlugins();

		if (!$this->mechanism->supportsAPI) {
			throw new Exception('The auth mechanism ' . $this->provider->mechanism .
			' does not support API access');
		}

		if (!$accessToken) {
			$accessToken = $this->Session->read($this->settings['sessionVariableAccessToken']);
		}

		return $this->mechanism->apiRequest(
			$this->provider,
			$accessToken,
			$url,
			$params,
			$method,
			$headers
		);
	}

	public function getNormalizedProfile($providerName, $accessToken = null) {
		$this->providerName = $providerName;
		$this->_setupPlugins();

		$response = $this->apiRequest(
			$this->provider,
			$this->provider->profileURL,
			$this->provider->profileParams,
			$accessToken,
			$this->provider->profileMethod,
			$this->provider->profileHeaders
		);

		if ($response['success']) {
			return array(
				'success'   => true,
				'data'      => $this->provider->normalizeProfile($response['data'])
			);
		} else {
			return $response;
		}
	}

	protected function _setupPlugins($providerName = null) {
		if (!$providerName) {
			$providerName = $this->providerName;
		}
		$this->provider = $this->_getPlugin(ucfirst($providerName), 'AuthProvider');
		$this->mechanism = $this->_getPlugin($this->provider->mechanism, 'AuthMechanism');
	}

	protected function _getPlugin($name, $type) {
		if (ClassRegistry::isKeySet($name . $type)) {
			return ClassRegistry::getObject($name . $type);
		}

		$className = $name . $type;
		$fileName = $className . '.php';

		foreach($this->settings[$type . 'Path'] as $path) {
			if (file_exists($path . $fileName)) {
				require($path . $fileName);
				$plugin = new $className;

				ClassRegistry::addObject($name . $type, $plugin);
				return $plugin;
			}
		}

		throw new Exception('ExtAuth plugin ' . $name . ' of type ' . $type . ' not found');
	}

	protected function _generateCallbackURL($callbackURL) {
		if (!$callbackURL) {
			$callbackURL = $this->settings['callbackURL'];
		}
		return str_replace(
			array('{PROVIDER}', '{SERVER_HOST}'),
			array(strtolower($this->providerName), $_SERVER['HTTP_HOST']),
			$this->settings['callbackURL']
		);
	}
}
