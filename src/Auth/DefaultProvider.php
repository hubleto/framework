<?php

namespace Hubleto\Framework\Auth;

use Hubleto\Framework\Router;
use Hubleto\Framework\Config;

class DefaultProvider implements \Hubleto\Framework\Interfaces\AuthInterface
{
  public array $user { get => $this->user; set (array $user) { $this->user = $user; } }

  public \Hubleto\Framework\Loader $main;

  public $loginAttribute = 'login';
  public $passwordAttribute = 'password';
  public $activeAttribute = 'is_active';
  public $verifyMethod = 'password_verify';

  function __construct(\Hubleto\Framework\Loader $main)
  {
    $this->main = $main;
    $this->user = [];
  }

  public function init(): void
  {
    $userLanguage = $this->getUserLanguage();
    if (empty($userLanguage)) $userLanguage = 'en';
    $this->main->getConfig()->set('language', $userLanguage);
  }

  public function getRouter(): Router
  {
    return $this->main->getRouter();
  }

  public function getConfig(): Config
  {
    return $this->main->getConfig();
  }

  public function getUserFromSession(): array
  {
    $tmp = $this->main->session->get('userProfile') ?? [];
    return [
      'id' => (int) ($tmp['id'] ?? 0),
      'login' => (string) ($tmp['login'] ?? ''),
      'is_active' => (bool) ($tmp['is_active'] ?? false),
    ];
  }

  public function updateUserInSession(array $user): void
  {
    $this->main->session->set('userProfile', $user);
  }

  public function isUserInSession(): bool
  {
    $user = $this->getUserFromSession();
    return isset($user['id']) && $user['id'] > 0;
  }

  public function loadUserFromSession()
  {
    $this->user = $this->getUserFromSession();
  }

  function deleteSession()
  {
    $this->main->session->clear();
    $this->user = [];

    setcookie($this->main->session->getSalt() . '-user', '', 0);
    setcookie($this->main->session->getSalt() . '-language', '', 0);
  }

  public function signIn(array $user)
  {
    $this->user = $user;
    $this->updateUserInSession($user);
  }

  public function signOut()
  {
    $this->deleteSession();
    $this->getRouter()->redirectTo('?signed-out');
    exit;
  }

  public function createUserModel(): \Hubleto\Framework\Model
  {
    return $this->main->load(\Hubleto\Framework\Models\User::class);
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

    $userModel = $this->createUserModel();

    if ($this->isUserInSession()) {
      $this->loadUserFromSession();
    } else {
      $login = $this->getRouter()->urlParamAsString('login');
      $password = $this->getRouter()->urlParamAsString('password');
      $rememberLogin = $this->getRouter()->urlParamAsBool('rememberLogin');

      $login = trim($login);

      if (empty($login) && !empty($_COOKIE[$this->main->session->getSalt() . '-user'])) {
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

  public function getUser(): array
  {
    return is_array($this->user) ? $this->user : [];
  }

  public function getUserType(): int
  {
    $user = $this->getUser();
    return $user['type'] ?? 0;
  }

  public function getUserRoles(): array
  {
    if (isset($this->user['ROLES']) && is_array($this->user['ROLES'])) return $this->user['ROLES'];
    else if (isset($this->user['roles']) && is_array($this->user['roles'])) return $this->user['roles'];
    else return [];
  }

  public function userHasRole(int $idRole): bool
  {
    return in_array($idRole, $this->getUserRoles());
  }

  public function getUserId(): int
  {
    return (int) ($this->user['id'] ?? 0);
  }

  public function getUserLanguage(): string
  {
    $language = (string) ($this->user['language'] ?? $this->main->getConfig()->getAsString('language'));
    return (strlen($language) == 2 ? $language : 'en');
  }

  public function forgotPassword(): void
  {
  }

  public function resetPassword(): void
  {
  }

  public function setUserLanguage(string $language): void {
    $user = $this->user;
    $user['language'] = $language;
    $this->user = $user;
  }

}
