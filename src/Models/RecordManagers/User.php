<?php

namespace Hubleto\Framework\Models\RecordManagers;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

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
