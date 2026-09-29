<?php

namespace Hubleto\Framework\Models\RecordManagers;

class Config extends \Hubleto\Framework\EloquentRecordManager {
  public static $snakeAttributes = false;
  public $table = 'config';

}
