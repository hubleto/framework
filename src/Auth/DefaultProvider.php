<?php

namespace Hubleto\Framework\Auth;

class DefaultProvider extends \Hubleto\Framework\Auth {
  public $loginAttribute = 'login';
  public $passwordAttribute = 'password';
  public $activeAttribute = 'is_active';
  public $verifyMethod = 'password_verify';

  function __construct(\Hubleto\Framework\Loader $main)
  {
    parent::__construct($main);

    $this->main->registerModel(\Hubleto\Legacy\Models\User::class);
    $this->main->registerModel(\Hubleto\Legacy\Models\UserRole::class);
    $this->main->registerModel(\Hubleto\Legacy\Models\UserHasRole::class);
  }

  public function init(): void
  {
    $userLanguage = $this->getUserLanguage();
    if (empty($userLanguage)) $userLanguage = 'en';
    $this->main->config->set('language', $userLanguage);
  }

  public function createUserModel(): \Hubleto\Framework\Model
  {
    return $this->main->di->create('model.user');
  }

  public function findUsersByLogin(string $login): array
  {
    return $this->createUserModel()->record
      ->orWhere($this->loginAttribute, $login)
      ->where($this->activeAttribute, '<>', 0)
      ->get()
      ->makeVisible([$this->passwordAttribute])
      ->toArray()
    ;
  }

  public function auth(): void
  {

    $userModel = $this->createUserModel();

    if ($this->isUserInSession()) {
      $this->loadUserFromSession();
    } else {
      $login = $this->main->urlParamAsString('login');
      $password = $this->main->urlParamAsString('password');
      $rememberLogin = $this->main->urlParamAsBool('rememberLogin');

      $login = trim($login);

      if (empty($login) && !empty($_COOKIE[$this->main->session->getSalt() . '-user'])) {
        $login = $userModel->authCookieGetLogin();
      }

      if (!empty($login) && !empty($password)) {
        $users = $this->findUsersByLogin($login);

        foreach ($users as $user) {
          $passwordMatch = FALSE;

          if ($this->verifyMethod == 'password_verify' && password_verify($password, $user[$this->passwordAttribute] ?? "")) {
            $passwordMatch = TRUE;
          }
          if ($this->verifyMethod == 'md5' && md5($password) == $user[$this->passwordAttribute]) {
            $passwordMatch = TRUE;
          }

          if ($passwordMatch) {
            $authResult = $userModel->loadUser($user['id']);
            $this->signIn($authResult);

            if ($rememberLogin) {
              setcookie(
                $this->main->session->getSalt() . '-user',
                $userModel->authCookieSerialize($user[$this->loginAttribute], $user[$this->passwordAttribute]),
                time() + (3600 * 24 * 30)
              );
            }

            break;

          }
        }
      }
    }

  }
}
