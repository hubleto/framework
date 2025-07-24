<?php

namespace Hubleto\Legacy\Models\RecordManagers;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHasRole extends \Hubleto\Legacy\Core\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'user_has_roles';

}
