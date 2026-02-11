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
      'email' => new \Hubleto\Framework\Db\Column\Email($this, 'Email'),
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

  /**
   * [Description for indexes]
   *
   * @param array $indexes
   * 
   * @return array
   * 
   */
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
   * [Description for findUsersByLogin]
   *
   * @param string $login
   * 
   * @return array
   * 
   */
  public function findUsersByLogin(string $login): array
  {
    return $this->record
      ->where('email', trim($login))
      ->where('is_active', '<>', 0)
      ->get()
      ->makeVisible(['password'])
      ->toArray()
    ;
  }

  /**
   * [Description for authCookieGetLogin]
   *
   * @return string
   * 
   */
  public function authCookieGetLogin(): string
  {
    if (!empty($_COOKIE[$this->sessionManager()->getSalt() . '-user'])) {
      list($tmpHash, $tmpLogin) = explode(",", $_COOKIE[$this->sessionManager()->getSalt() . '-user']);
      return $tmpLogin;
    } else {
      return '';
    }
  }

  /**
   * [Description for loadUser]
   *
   * @param int $idUser
   * 
   * @return array
   * 
   */
  public function loadUser(mixed $uidUser): array
  {
    $idUser = (int) $uidUser;

    $user = $this->record
      ->where('id', $idUser)
      ->where('is_active', '<>', 0)
      ->first()
      ?->toArray()
    ;


    return $user;
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
   * @param mixed $uidUser
   * @param string $password
   * 
   * @return array
   * 
   */
  public function updatePassword(mixed $uidUser, string $password): array
  {
    $idUser = (int) $uidUser;
    return $this->record
      ->where('id', $idUser)
      ->update(
        ["password" => $this->encryptPassword($password)]
      )
    ;
  }

  /**
   * [Description for verifyPassword]
   *
   * @param array $user
   * @param string $password
   * 
   * @return bool
   * 
   */
  public function verifyPassword(array $user, string $password): bool
  {
    return password_verify($password, $user['password'] ?? '');
  }

}
