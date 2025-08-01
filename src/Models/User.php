<?php

namespace Hubleto\Framework\Models;

class User extends \Hubleto\Framework\Model {
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


  public function __construct(public \Hubleto\Framework\Loader $main)
  {
    parent::__construct($main);

    $tokenModel = $main->getModel("Hubleto/Framework/Models/Token");

    if (!$tokenModel->isTokenTypeRegistered(self::TOKEN_TYPE_USER_FORGOT_PASSWORD)) {
      $tokenModel->registerTokenType(self::TOKEN_TYPE_USER_FORGOT_PASSWORD);
    }
  }

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'login' => new \Hubleto\Framework\Db\Column\Varchar($this, 'Login'),
      'password' => new \Hubleto\Framework\Db\Column\Password($this, 'Password'),
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

  public function getClientIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  public function updateAccessInformation(int $idUser) {
    $clientIp = $this->getClientIpAddress();
    $this->record->where('id', $idUser)->update([
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  public function updateLoginAndAccessInformation(int $idUser) {
    $clientIp = $this->getClientIpAddress();
    $this->record->where('id', $idUser)->update([
      'last_login_time' => date('Y-m-d H:i:s'),
      'last_login_ip' => $clientIp,
      'last_access_time' => date('Y-m-d H:i:s'),
      'last_access_ip' => $clientIp,
    ]);
  }

  public function isUserActive($user): bool {
    return $user['is_active'] == 1;
  }

  public function authCookieGetLogin() {
    list($tmpHash, $tmpLogin) = explode(",", $_COOKIE[$this->main->session->getSalt() . '-user']);
    return $tmpLogin;
  }

  public function authCookieSerialize($login, $password) {
    return md5($login.".".$password).",".$login;
  }

  public function generateToken($idUser, $tokenSalt, $tokenType) {
    $tokenModel = $this->main->getModel("Hubleto/Framework/Models/Token");
    $token = $tokenModel->generateToken($tokenSalt, $tokenType);

    $this->record->updateRow([
      "id_token_reset_password" => $token['id'],
    ], $idUser);

    return $token['token'];
  }

  public function generatePasswordResetToken($idUser, $tokenSalt) {
    return $this->generateToken(
      $idUser,
      $tokenSalt,
      self::TOKEN_TYPE_USER_FORGOT_PASSWORD
    );
  }

  public function validateToken($token, $deleteAfterValidation = TRUE) {
    $tokenModel = $this->main->getModel("Hubleto/Framework/Models/Token");
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

  public function getQueryForUser(int $idUser) {
    return $this->record
      ->with('roles')
      ->where('id', $idUser)
      ->where('is_active', '<>', 0)
    ;
  }

  public function loadUser(int $idUser) {
    $user = $this->getQueryForUser($idUser)->first()?->toArray();

    $tmpRoles = [];
    foreach ($user['roles'] ?? [] as $role) {
      $tmpRoles[] = (int) $role['pivot']['id_role'];
    }
    $user['roles'] = $tmpRoles;

    return $user;
  }

  public function getByEmail(string $email) {
    $user = $this->record->where("email", $email)->first();

    return !empty($user) ? $user->toArray() : [];
  }

  public function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
  }

  public function updatePassword(int $idUser, string $password) {
    return $this->record
      ->where('id', $idUser)
      ->update(
        ["password" => $this->hasPassword($password)]
      )
    ;
  }

}
