<?php

namespace Hubleto\Framework\Models\RecordManagers;

class UserHasRole extends \Hubleto\Framework\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'user_has_roles';

}
