<?php

namespace Hubleto\Legacy\Core\Db\Column;

use \Hubleto\Legacy\Core\Db\ColumnProperty\Autocomplete;

class Virtual extends \Hubleto\Legacy\Core\Db\Column
{

  protected string $type = 'virtual';
  protected int $byteSize = 255;
  protected ?Autocomplete $autocomplete = null;

  public function __construct(\Hubleto\Legacy\Core\Model $model, string $title, int $byteSize = 255)
  {
    parent::__construct($model, $title);
    $this->byteSize = $byteSize;
  }

  public function sqlCreateString(string $table, string $columnName): string
  {
    return "";
  }

}