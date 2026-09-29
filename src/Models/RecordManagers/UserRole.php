<?php

namespace Hubleto\Framework\Models\RecordManagers;

class UserRole extends \Hubleto\Framework\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'user_roles';

}
