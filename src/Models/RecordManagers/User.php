<?php

namespace Hubleto\Framework\Models\RecordManagers;

class User extends \Hubleto\Framework\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'users';

  protected $hidden = [
    'password',
    'last_access_time',
    'last_access_ip',
    'last_login_time',
    'last_login_ip',
  ];

}
