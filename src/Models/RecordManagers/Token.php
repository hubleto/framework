<?php

namespace Hubleto\Framework\Models\RecordManagers;

class Token extends \Hubleto\Framework\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'tokens';

}
