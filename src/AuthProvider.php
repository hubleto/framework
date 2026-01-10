<?php

namespace Hubleto\Framework;

/**
 * Default implementation of authentication provider.
 */
class AuthProvider extends Core implements Interfaces\AuthInterface
{

  public $loginAttribute = 'login';
  public $passwordAttribute = 'password';
  public $activeAttribute = 'is_active';
  public $verifyMethod = 'password_verify';

  public bool $logInfo = false;

  public array $user = [];

  public function init(): void
  {
    $userLanguage = $this->getUserLanguage();
    if (empty($userLanguage)) $userLanguage = 'en';
    $this->config()->set('language', $userLanguage);
  }

  /**
   * [Description for normalizeUserProfile]
   *
   * @param array $user
   * 
   * @return array
   * 
   */
  public function normalizeUserProfile(array $user): array
  {
    return [
      'id' => (int) ($user['id'] ?? 0),
      'email' => (string) ($user['email'] ?? ''),
      'login' => (string) ($user['login'] ?? ''),
      'is_active' => (bool) ($user['is_active'] ?? false),
    ];
  }

  /**
   * Get user information from the session.
   *
   * @return array
   * 
   */
  public function getUserFromSession(): array
  {
    $tmp = $this->sessionManager()->get('userProfile') ?? [];
    return $this->normalizeUserProfile($tmp);
  }

  /**
   * [Description for updateUserInSession]
   *
   * @param array $user
   * 
   * @return void
   * 
   */
  public function updateUserInSession(array $user): void
  {
    $this->sessionManager()->set('userProfile', $user);
  }

  /**
   * [Description for isUserInSession]
   *
   * @return bool
   * 
   */
  public function isUserInSession(): bool
  {
    $user = $this->getUserFromSession();
    return isset($user['id']) && $user['id'] > 0;
  }

  /**
   * [Description for deleteSession]
   *
   * @return [type]
   * 
   */
  function deleteSession()
  {
    $this->sessionManager()->clear();
    $this->user = [];

    setcookie($this->sessionManager()->getSalt() . '-user', '', 0);
    setcookie($this->sessionManager()->getSalt() . '-language', '', 0);
  }

  /**
   * [Description for signIn]
   *
   * @param array $user
   * 
   * @return [type]
   * 
   */
  public function signIn(array $user)
  {
    $this->user = $user;
    $this->updateUserInSession($user);
  }

  /**
   * [Description for signOut]
   *
   * @return [type]
   * 
   */
  public function signOut()
  {
    $this->deleteSession();
    $this->router()->redirectTo('?signed-out');
    exit;
  }

  /**
   * [Description for createUserModel]
   *
   * @return Model
   * 
   */
  public function createUserModel(): Model
  {
    return $this->getModel(Models\User::class);
  }

  /**
   * [Description for findUsersByLogin]
   *
   * @param string $login
   * 
   * @return array
   * 
   */
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

  /**
   * [Description for verifyPassword]
   *
   * @param mixed $password1
   * @param mixed $password2
   * 
   * @return bool
   * 
   */
  public function verifyPassword($password1, $password2): bool
  {
    return password_verify($password1, $password2);
  }

  public function getActiveUsers(): array
  {
    return (array) $this->createUserModel()->record
      ->where($this->activeAttribute, '<>', 0)
      ->get()
      ->toArray()
    ;
  }

  public function auth(): void
  {

    /** @var Models\User */
    $userModel = $this->createUserModel();

    if (!$this->isUserInSession()) {
      $login = $this->router()->urlParamAsString('login');
      $password = $this->router()->urlParamAsString('password');
      $rememberLogin = $this->router()->urlParamAsBool('session-persist');

      $login = trim($login);

      if (empty($login) && !empty($_COOKIE[$this->sessionManager()->getSalt() . '-user'])) {
        $login = $userModel->authCookieGetLogin();
      }

      if (!empty($login) && !empty($password)) {
        $users = $this->findUsersByLogin($login);

        foreach ($users as $user) {
          $passwordMatch = $this->verifyPassword($password, $user[$this->passwordAttribute]);

          if ($passwordMatch) {
            $authResult = $userModel->loadUser($user['id']);
            $this->signIn($authResult);

            if ($rememberLogin) {
              $this->sessionManager()->prolongSession();
            }

            break;

          }
        }
      }
    }

  }

  /**
   * [Description for getUser]
   *
   * @return array
   * 
   */
  public function getUser(): array
  {
    return $this->getUserFromSession();
  }

  /**
   * [Description for getUserFromDatabase]
   *
   * @return array
   * 
   */
  public function getUserFromDatabase(): array
  {
    $mUser = $this->createUserModel();

    return $this->normalizeUserProfile(
      $mUser->record
        ->where($mUser->table . '.id', $this->getUserId())
        ->first()
        ->toArray()
    );
  }

  public function getUserType(): int
  {
    $user = $this->getUser();
    return $user['type'] ?? 0;
  }

  public function getUserRoles(): array
  {
    $user = $this->getUser();

    $roles = [];

    foreach ($user['ROLES'] ?? [] as $tmpRole) {
      if (is_array($tmpRole)) $roles[] = (int) $tmpRole['id'];
      else $roles[] = (int) $tmpRole;
    }

    return $roles;
  }

  public function userHasRole(int $idRole): bool
  {
    return in_array($idRole, $this->getUserRoles());
  }

  public function getUserId(): int
  {
    return (int) ($this->getUser()['id'] ?? 0);
  }

  public function getUserEmail(): string
  {
    return (string) ($this->getUser()['email'] ?? '');
  }

  public function forgotPassword(): void
  {
  }

  public function resetPassword(): void
  {
  }

  /**
   * [Description for getUserLanguage]
   *
   * @return string
   * 
   */
  public function getUserLanguage(): string
  {
    $user = $this->getUserFromSession() ?? [];
    if (isset($user['language']) && strlen($user['language']) == 2) {
      return $user['language'];
    } else if (isset($_COOKIE['language']) && strlen($_COOKIE['language']) == 2) {
      return $_COOKIE['language'];
    } else {
      $language = $this->config()->getAsString('language', 'en');
      if (strlen($language) !== 2) $language = 'en';
      return $language;
    }
  }

  /**
   * [Description for setUserLanguage]
   *
   * @param string $language
   * 
   * @return void
   * 
   */
  public function setUserLanguage(string $language): void {
    $user = $this->getUser();
    $user['language'] = $language;
    $this->updateUserInSession($user);
  }

}
