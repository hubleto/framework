<?php

namespace Hubleto\Framework\Auth;

class KeycloakOAuth2Provider extends \Hubleto\Framework\AuthProvider
{

  public $provider;
  public bool $logInfo = false;

  function init(): void
  {
    /** @disregard P1009 */
    $this->provider = new \League\OAuth2\Client\Provider\GenericProvider([
      'clientId' => $this->config()->getAsString('auth/clientId'),
      'clientSecret' => $this->config()->getAsString('auth/clientSecret'),
      'redirectUri' => $this->config()->getAsString('auth/redirectUri'),
      'urlAuthorize' => $this->config()->getAsString('auth/urlAuthorize'),
      'urlAccessToken' => $this->config()->getAsString('auth/urlAccessToken'),
      'urlResourceOwnerDetails' => $this->config()->getAsString('auth/urlResourceOwnerDetails'),
    ], [
      'httpClient' => new \GuzzleHttp\Client([\GuzzleHttp\RequestOptions::VERIFY => false]),
    ]);

    $this->logInfo = $this->config()->getAsBool('auth/logInfo');

  }

  public function getUserFromSession(): array
  {
    $tmp = $this->sessionManager()->get('userProfile') ?? [];
    return [
      'id' => (int) ($tmp['id'] ?? 0),
      'username' => (string) ($tmp['username'] ?? ''),
      'email' => (string) ($tmp['email'] ?? ''),
      'login' => (string) ($tmp['login'] ?? ''),
      'is_active' => (bool) ($tmp['is_active'] ?? false),
    ];
  }

  public function isUserInSession(): bool
  {
    $user = $this->getUserFromSession();
    return isset($user['username']) && !empty($user['username']);
  }

  public function signOut()
  {
    $accessToken = $this->getAccessToken();
    try {
      $idToken = $accessToken->getValues()['id_token'] ?? '';

      $this->deleteSession();
      header(
        "Location: " . $this->config()->getAsString('auth/urlLogout') . 
        "?id_token_hint=".\urlencode($idToken)."&post_logout_redirect_uri=".\urlencode($this->env()->projectUrl . "?signed-out")
      );
      exit;
    } catch (\Throwable $e) {
      $this->deleteSession();
      header("Location: {$this->env()->projectUrl}");
      exit;
    }
  }

  public function getAccessToken()
  {
    return $this->sessionManager()->get('oauthAccessToken');
  }

  public function setAccessToken($accessToken)
  {
    $this->sessionManager()->set('oauthAccessToken', $accessToken);
  }

  public function auth(): void
  {
    if ($this->logInfo) $this->logger()->info('Keycloak: auth()');
    $accessToken = $this->getAccessToken();
    if ($this->logInfo) $this->logger()->info('Keycloak: accessToken length = ' . strlen($accessToken));

    if ($accessToken && $accessToken->hasExpired()) {
      try {
        $accessToken = $this->provider->getAccessToken('refresh_token', [
          'refresh_token' => $accessToken->getRefreshToken()
        ]);
        $this->setAccessToken($accessToken);
      } catch (\Exception $e) {
        $this->deleteSession();
      }
    }

    if ($accessToken) {
      if ($this->logInfo) $this->logger()->info('Keycloak: if accessToken');

      try {
        $resourceOwner = $this->provider->getResourceOwner($accessToken);

        if ($resourceOwner) {
          if ($this->logInfo) $this->logger()->info('Keycloak: resourceOwner = ' . print_r($resourceOwner->toArray(), true));
          $this->signIn($resourceOwner->toArray());
        } else {
          if ($this->logInfo) $this->logger()->info('Keycloak: delete session');
          $this->deleteSession();
        }

      } catch (\Exception $e) {
        $this->deleteSession();
      }
    } else {

      $authCode = $this->router()->urlParamAsString('code');
      $authState = $this->router()->urlParamAsString('state');

      if ($this->logInfo) $this->logger()->info('Keycloak: authCode = ' . $authCode . ', authState = ' . $authState);

      // If we don't have an authorization code then get one
      if (empty($authCode)) {

        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $this->provider->getAuthorizationUrl(['scope' => ['openid']]);

        // Get the state generated for you and store it to the session.
        $this->sessionManager()->set('oauth2state', $this->provider->getState());

        // Optional, only required when PKCE is enabled.
        // Get the PKCE code generated for you and store it to the session.
        $this->sessionManager()->set('oauth2pkceCode', $this->provider->getPkceCode());

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;

      // Check given state against previously stored one to mitigate CSRF attack
      } elseif (
        empty($authState)
        || empty($this->sessionManager()->get('oauth2state'))
        || $authState !== $this->sessionManager()->get('oauth2state')
      ) {
        if ($this->sessionManager()->isset('oauth2state')) $this->sessionManager()->unset('oauth2state');
        exit('Invalid state');
      } else {

        try {

          // Optional, only required when PKCE is enabled.
          // Restore the PKCE code stored in the session.
          $this->provider->setPkceCode($this->sessionManager()->get('oauth2pkceCode'));

          // Try to get an access token using the authorization code grant.
          $accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $authCode
          ]);

          $this->setAccessToken($accessToken);

          // Using the access token, we may look up details about the
          // resource owner.
          $resourceOwner = $this->provider->getResourceOwner($accessToken);

          $authResult = $resourceOwner->toArray();

          if ($this->logInfo) $this->logger()->info('Keycloak: authResult' . var_dump($authResult));

        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

          if ($e->getMessage() == 'invalid_grant') {
          }

          // Failed to get the access token or user details.
          exit($e->getMessage());

        }
      }

      if ($authResult) {
        $this->signIn($authResult);
        $this->router()->redirectTo('');
        exit;
      } else {
        $this->deleteSession();
      }
    }
  }
}
