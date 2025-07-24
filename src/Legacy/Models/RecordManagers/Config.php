<?php

namespace Hubleto\Legacy\Models\RecordManagers;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class Config extends \Hubleto\Legacy\Core\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'config';

}
