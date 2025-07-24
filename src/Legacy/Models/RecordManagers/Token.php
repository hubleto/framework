<?php

namespace Hubleto\Legacy\Models\RecordManagers;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends \Hubleto\Legacy\Core\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'tokens';

}
