<?php

namespace Hubleto\Framework\Models;

use Hubleto\Framework\Model;

class User extends Model {
  const TOKEN_TYPE_USER_FORGOT_PASSWORD = 551155;

  public string $table = "users";

  protected $hidden = [
    'password',
    'last_access_time',
    'last_access_ip',
    'last_login_time',
    'last_login_ip',
  ];

  public string $urlBase = "users";
  public ?string $lookupSqlValue = "{%TABLE%}.login";
  public string $recordManagerClass = RecordManagers\User::class;

  public ?array $junctions = [
    'roles' => [
      'junctionModel' => \Hubleto\Framework\Models\UserHasRole::class,
      'masterKeyColumn' => 'id_user',
      'optionKeyColumn' => 'id_role',
    ],
  ];


  public function __construct()
  {
    parent::__construct();

    /** @var \Hubleto\Framework\Models\Token $tokenModel */
    $tokenModel = $this->getModel(\Hubleto\Framework\Models\Token::class);

    if (!$tokenModel->isTokenTypeRegistered(self::TOKEN_TYPE_USER_FORGOT_PASSWORD)) {
      $tokenModel->registerTokenType(self::TOKEN_TYPE_USER_FORGOT_PASSWORD);
    }
  }

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'email' => new \Hubleto\Framework\Db\Column\Varchar($this, 'Email'),
      'login' => new \Hubleto\Framework\Db\Column\Varchar($this, 'Login'),
      'password' => new \Hubleto\Framework\Db\Column\Password($this, 'Password'),
      'type' => new \Hubleto\Framework\Db\Column\Integer($this, 'Type'),
      'is_active' => new \Hubleto\Framework\Db\Column\Boolean($this, 'Active'),
      'last_login_time' => new \Hubleto\Framework\Db\Column\DateTime($this, 'Time of last login'),
      'last_login_ip' => new \Hubleto\Framework\Db\Column\Varchar($this, 'Last login IP'),
      'last_access_time' => new \Hubleto\Framework\Db\Column\DateTime($this, 'Time of last access'),
      'last_access_ip' => new \Hubleto\Framework\Db\Column\Varchar($this, 'Last access IP'),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    unset($description->columns['password']);
    return $description;
  }

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      "login" => [
        "type" => "unique",
        "columns" => [
          "login" => [
            "order" => "asc",
          ],
        ],
      ],
    ]);
  }

  /**
   * [Description for getClientIpAddress]
   *
   * @return string
   * 
   */
  public function getClientIpAddress(): string
  {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  /**
   * [Description for updateAccessInformation]
   *
   * @param int $idUser
   * 
   * @return void
   * 
   */
  public function updateAccessInformation(int $idUser): void
  {
    $clientIp = $this->getClientIpAddress();
    $this->record->where('id', $idUser)->update([
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  /**
   * [Description for updateLoginAndAccessInformation]
   *
   * @param int $idUser
   * 
   * @return void
   * 
   */
  public function updateLoginAndAccessInformation(int $idUser): void
  {
    $clientIp = $this->getClientIpAddress();
    $this->record->where('id', $idUser)->update([
      'last_login_time' => date('Y-m-d H:i:s'),
      'last_login_ip' => $clientIp,
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  /**
   * [Description for isUserActive]
   *
   * @param mixed $user
   * 
   * @return bool
   * 
   */
  public function isUserActive($user): bool
  {
    return $user['is_active'] == 1;
  }

  /**
   * [Description for authCookieGetLogin]
   *
   * @return string
   * 
   */
  public function authCookieGetLogin(): string
  {
    list($tmpHash, $tmpLogin) = explode(",", $_COOKIE[$this->sessionManager()->getSalt() . '-user']);
    return $tmpLogin;
  }

  /**
   * [Description for authCookieSerialize]
   *
   * @param mixed $login
   * @param mixed $password
   * 
   * @return string
   * 
   */
  public function authCookieSerialize($login, $password): string
  {
    return md5($login.".".$password).",".$login;
  }

  /**
   * [Description for generateToken]
   *
   * @param mixed $idUser
   * @param mixed $tokenSalt
   * @param mixed $tokenType
   * 
   * @return string
   * 
   */
  public function generateToken($idUser, $tokenSalt, $tokenType): string
  {
    /** @var \Hubleto\Framework\Models\Token $tokenModel */
    $tokenModel = $this->getModel(\Hubleto\Framework\Models\Token::class);
    $token = $tokenModel->generateToken($tokenSalt, $tokenType);

    $this->record->updateRow([
      "id_token_reset_password" => $token['id'],
    ], $idUser);

    return $token['token'];
  }

  /**
   * [Description for generatePasswordResetToken]
   *
   * @param mixed $idUser
   * @param mixed $tokenSalt
   * 
   * @return string
   * 
   */
  public function generatePasswordResetToken($idUser, $tokenSalt): string
  {
    return $this->generateToken(
      $idUser,
      $tokenSalt,
      self::TOKEN_TYPE_USER_FORGOT_PASSWORD
    );
  }

  /**
   * [Description for validateToken]
   *
   * @param mixed $token
   * @param bool $deleteAfterValidation
   * 
   * @return array
   * 
   */
  public function validateToken($token, $deleteAfterValidation = true): array
  {
    /** @var \Hubleto\Framework\Models\Token $tokenModel */
    $tokenModel = $this->getModel(\Hubleto\Framework\Models\Token::class);
    $tokenData = $tokenModel->validateToken($token);

    $userData = $this->record->where(
      'id_token_reset_password', $tokenData['id']
      )->first()
    ;

    if (!empty($userData)) {
      $userData = $userData->toArray();
    }

    if ($deleteAfterValidation) {
      $this->record->updateRow([
        "id_token_reset_password" => NULL,
      ], $userData["id"]);

      $tokenModel->deleteToken($tokenData['id']);
    }

    return $userData;
  }

  /**
   * [Description for getQueryForUser]
   *
   * @param int $idUser
   * 
   * @return mixed
   * 
   */
  public function getQueryForUser(int $idUser): mixed
  {
    return $this->record
      ->where('id', $idUser)
      ->where('is_active', '<>', 0)
    ;
  }

  /**
   * [Description for loadUser]
   *
   * @param int $idUser
   * 
   * @return array
   * 
   */
  public function loadUser(int $idUser): array
  {
    $user = $this->getQueryForUser($idUser)->first()?->toArray();

    $tmpRoles = [];
    foreach ($user['roles'] ?? [] as $role) {
      $tmpRoles[] = (int) $role['pivot']['id_role'];
    }
    $user['roles'] = $tmpRoles;

    return $user;
  }

  /**
   * [Description for getByEmail]
   *
   * @param string $email
   * 
   * @return array
   * 
   */
  public function getByEmail(string $email): array
  {
    $user = $this->record->where("email", $email)->first();

    return !empty($user) ? $user->toArray() : [];
  }

  /**
   * [Description for encryptPassword]
   *
   * @param string $password
   * 
   * @return string
   * 
   */
  public function encryptPassword(string $password): string
  {
    return password_hash($password, PASSWORD_DEFAULT);
  }

  /**
   * [Description for updatePassword]
   *
   * @param int $idUser
   * @param string $password
   * 
   * @return mixed
   * 
   */
  public function updatePassword(int $idUser, string $password): mixed
  {
    return $this->record
      ->where('id', $idUser)
      ->update(
        ["password" => $this->encryptPassword($password)]
      )
    ;
  }

}
