<?php

namespace ADIOS\Models\RecordManagers;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends \ADIOS\Core\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'tokens';

}
