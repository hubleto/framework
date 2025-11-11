<?php

namespace Hubleto\Framework;

/**
 * Default implementation of authentication provider.
 */
class AuthProvider extends Core implements Interfaces\AuthInterface
{

  public string $loginAttribute = 'login';
  public string $passwordAttribute = 'password';
  public string $activeAttribute = 'is_active';
  public string $verifyMethod = 'password_verify';

  public bool $logInfo = false;

  public array $user = [];

  public function init(): void
  {
    $userLanguage = $this->getUserLanguage();
    if (empty($userLanguage)) $userLanguage = 'en';
    $this->config()->set('language', $userLanguage);
  }

  public function getUserFromSession(): array
  {
    $tmp = $this->sessionManager()->get('userProfile') ?? [];
    return [
      'id' => (int) ($tmp['id'] ?? 0),
      'email' => (string) ($tmp['email'] ?? ''),
      'login' => (string) ($tmp['login'] ?? ''),
      'is_active' => (bool) ($tmp['is_active'] ?? false),
    ];
  }

  public function updateUserInSession(array $user): void
  {
    $this->sessionManager()->set('userProfile', $user);
    if ($this->logInfo) $this->logger()->info('Auth: updateUserInSession' . var_dump($user));
  }

  public function isUserInSession(): bool
  {
    $user = $this->getUserFromSession();
    return isset($user['id']) && $user['id'] > 0;
  }

  function deleteSession()
  {
    $this->sessionManager()->clear();
    $this->user = [];

    setcookie($this->sessionManager()->getSalt() . '-user', '', 0);
    setcookie($this->sessionManager()->getSalt() . '-language', '', 0);
  }

  public function signIn(array $user)
  {
    $this->user = $user;
    $this->updateUserInSession($user);
  }

  public function signOut()
  {
    $this->deleteSession();
    $this->router()->redirectTo('?signed-out');
    exit;
  }

  public function createUserModel(): Model
  {
    return $this->getModel(Models\User::class);
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

  public function getUser(): array
  {
    return $this->getUserFromSession();
  }

  public function getUserType(): int
  {
    $user = $this->getUser();
    return $user['type'] ?? 0;
  }

  public function getUserRoles(): array
  {
    $user = $this->getUser();
    if (isset($user['ROLES']) && is_array($user['ROLES'])) return $user['ROLES'];
    else if (isset($user['roles']) && is_array($user['roles'])) return $user['roles'];
    else return [];
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
    public function setUserLanguage(string $language): void {
    $user = $this->getUser();
    $user['language'] = $language;
    $this->updateUserInSession($user);
  }

}
