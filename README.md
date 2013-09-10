# ExtAuth #

Copyright © 2013 Scott Donnelly ( http://scott.donnel.ly )

Licensed under the MIT license. Redistributions of files must retain the above copyright notice.

## Synopsis ##

A CakePHP 2.x Plugin for quick, easy federated login / external authentication (via OAuth1 and OAuth2).

ExtAuth is designed to have you able to log in to your CakePHP app via Facebook, Twitter, and/or Google,
straight out of the box. Any other OAuth 1, OAuth 1a or OAuth 2 supporting providers should be fairly
simple to add.

ExtAuth does not provide a User Model or User Controller. It is provided as a Component that is to be
integrated into your own (or another plugin's) User Controller. An example of how to do this is below.


## Installation ##

The recommended, easiest and quickest way to install ExtAuth is via Composer. To install via Composer, use the following steps:

1. From terminal, change directory to the app/ directory.
2. Install composer locally if you do not have it installed already. This can be done with the following command:

	```bash
	curl -sS https://getcomposer.org/installer | php
	```
	
	Or, alternatively, follow the instructions at http://getcomposer.org/doc/00-intro.md#installation-nix
3. If you don't have a composer.json file in app/ already, create one, with this as the contents:
	```JSON
		{
			"require": {
				"sc0ttyd/ext-auth": "dev-master"
			},
			"config": {
				"vendor-dir": "Vendor/"
			}
		}
	```
	If you already have a composer.json file, merge the above into it.
4. run the following command:
	```bash
		php composer.phar install
	```
	This should install ExtAuth to your app/Plugin folder, and it's dependancy, EHER/OAuth, to the app/Vendor folder.

5. At the top of your app/Config/bootstrap.php file, add the following:
	```php
	require APP . '/Vendor/autoload.php';
	spl_autoload_unregister(array('App', 'load'));
	spl_autoload_register(array('App', 'load'), true, true);
	```
	This will ensure that Cake can find all of the files we just installed. For more information on this, see http://mark-story.com/posts/view/installing-cakephp-with-composer and https://github.com/composer/composer/commit/c80cb76b9b5082ecc3e5b53b1050f76bb27b127b

The above may look a bit daunting if you aren't used to Composer, but, trust me, it's worth becoming familiar with it, if you are not already. Composer makes things much easier once you are familiar with it. If you choose to install by the more traditional technique of copying the files in this repo to app/Plugin/ExtAuth (or doing the same with git submodules), you will need to make a clone of https://github.com/EHER/OAuth in the app/Vendor directory, and add App::Import() statements or 'require_once' statements to ExtAuth/Controller/Component/AuthMechanisms/OAuth1AuthMechanism.php so that Util, Token, Consumer, Request, and HmacSha1 get loaded.


## Configuration ##

1. Make sure that the plugin gets loaded by CakePHP. To do this, place the line
		CakePlugin::load('ExtAuth');
	at the bottom of your Config/bootstrap.php file.

2. For each of the login providers that you wish to use, you need to create an app/project, set the callback URLs, obtain an app key
	and an app secret, and tell ExtAuth what these are. Here's how:

	### Google ###

	1. Browse to https://code.google.com/apis/console.
	2. Create a project, if you have not already.
	3. Click on API Access
	4. Create a client ID. You need to set the Redirect URI to http://example.com/auth_callback/google
		(replace example.com with your domain name. You can't use an invalid TLD, such as example.dev,
		for testing - Google does not allow this. you'll need to use dev.example.com instead, as well
		as your example.com live domain.) The path of the callback can be changed in the ExtAuth component's settings.
	5. Create two lines in your app's Config/core.php file (or elsewhere if you have another location for app settings)
		similar to the following:

		```php
		Configure::write('ExtAuth.Provider.Google.key', '123456789012.apps.googleusercontent.com');
		Configure::write('ExtAuth.Provider.Google.secret', 'blahblahblahblahblahblah');
		```

		Replace the config values with the Client ID and Client secret for your project in the Google API console.

	### Twitter ###

	1. Browse to https://dev.twitter.com/apps
	2. Create an application if you have not got one already. The Callback URL needs to be http://example.com/auth_callback/twitter.
		The path of the callback can be changed in the ExtAuth component's settings if needs be.
	3. Create two lines in your app's Config/core.php file (or elsewhere if you have another location for app settings)
		similar to the following:

		```php
		Configure::write('ExtAuth.Provider.Twitter.key', 'blahblahblah');
		Configure::write('ExtAuth.Provider.Twitter.secret', 'blahblahblahblahblahblah');
		```

		Replace the config values with the Consumer key and Consumer secret for your app on the Twitter apps Details tab.
	4. Make sure "Sign In With Twitter" is ticked in the settings for your app on Twitter.

	### Facebook ###

	1. Browse to https://developers.facebook.com/apps.
	2. Create an app, if you have not done so already. Set the "App Domain" as the domain name of your CakePHP site.
	3. Click on "Website with Facebook Login". set the site URL as the URL of your CakePHP site.
	4. Create two lines in your app's Config/core.php file (or elsewhere if you have another location for app settings)
		similar to the following:

		```php
		Configure::write('ExtAuth.Provider.Facebook.key', '431235136634241414');
		Configure::write('ExtAuth.Provider.Facebook.secret', '12344g4f3241e4d2144');
		```

		Replace the config values with the App ID and App Secret for your app in Facebook.

## Usage ##

Firstly, ensure that your User Controller is loading the ExtAuth component, as well as CakePHP's Auth component:

```php
public $components = array('ExtAuth.ExtAuth', 'Auth', 'Session');
```

You will need, at minimum, two actions in your User Controller. These will initiate the authentication and handle a callback from
the provider. Something like this:

```php
public function auth_login($provider) {
	$result = $this->ExtAuth->login($provider);
	if ($result['success']) {

		$this->redirect($result['redirectURL']);

	} else {
		$this->Session->setFlash($result['message']);
		$this->redirect($this->Auth->loginAction);
	}
}

public function auth_callback($provider) {
	$result = $this->ExtAuth->loginCallback($provider);
	if ($result['success']) {

		$this->__successfulExtAuth($result['profile'], $result['accessToken']);

	} else {
		$this->Session->setFlash($result['message']);
		$this->redirect($this->Auth->loginAction);
	}
}
```

You will also need to create two routes in Config/routes.php, similar to the following:

```php
Router::connect('/auth_login/*', array( 'controller' => 'users', 'action' => 'auth_login'));
Router::connect('/auth_callback/*', array( 'controller' => 'users', 'action' => 'auth_callback'));
```

That's it. I'll leave it to you to implement the __successfulExtAuth function, but, you might want something similar to this:

```php
private function __successfulExtAuth($incomingProfile, $accessToken) {

	// search for profile
	$this->SocialProfile->recursive = -1;
	$existingProfile = $this->SocialProfile->find('first', array(
		'conditions' => array('oid' => $incomingProfile['oid'])
	));

	if ($existingProfile) {

		// Existing profile? log the associated user in.
		$user = $this->User->find('first', array(
			'conditions' => array('id' => $existingProfile['SocialProfile']['user_id'])
		));

		$this->__doAuthLogin($user);
	} else {

		// New profile.
		if ($this->Auth->loggedIn()) {

			// user logged in already, attach profile to logged in user.

			// create social profile linked to current user
			$incomingProfile['user_id'] = $this->Auth->user('id');
			$this->SocialProfile->save($incomingProfile);
			$this->Session->setFlash('Your ' . $incomingProfile['provider'] . ' account has been linked.');
			$this->redirect($this->Auth->loginRedirect);

		} else {

			// no-one logged in, must be a registration.
			unset($incomingProfile['id']);
			$user = $this->User->register(array('User' => $incomingProfile));

			// create social profile linked to new user
			$incomingProfile['user_id'] = $user['User']['id'];
			$incomingProfile['last_login'] = date('Y-m-d h:i:s');
			$incomingProfile['access_token'] = serialize($accessToken);
			$this->SocialProfile->save($incomingProfile);

			// populate user detail fields that can be extracted
			// from social profile
			$profileData = array_intersect_key(
				$incomingProfile,
				array_flip(array(
					'email',
					'given_name',
					'family_name',
					'picture',
					'gender',
					'locale',
					'birthday',
					'raw'
				))
			);

			$this->User->setupDetail();
			$this->User->UserDetail->saveSection(
				$user['User']['id'],
				array('UserDetail' => $profileData),
				'User'
			);

			// log in
			$this->__doAuthLogin($user);
		}
	}
}

private function __doAuthLogin($user) {
	if ($this->Auth->login($user['User'])) {
		$user['last_login'] = date('Y-m-d H:i:s');
		$this->User->save(array('User' => $user));

		$this->Session->setFlash(sprintf(__d('users', '%s you have successfully logged in'), $this->Auth->user('username')));
		$this->redirect($this->Auth->loginRedirect);
	}
}
```
