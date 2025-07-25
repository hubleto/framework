<?php

namespace Hubleto\Framework\Db\Column;

use \Hubleto\Framework\Db\ColumnProperty\Autocomplete;

class Virtual extends \Hubleto\Framework\Db\Column
{

  protected string $type = 'virtual';
  protected int $byteSize = 255;
  protected ?Autocomplete $autocomplete = null;

  public function __construct(\Hubleto\Framework\Model $model, string $title, int $byteSize = 255)
  {
    parent::__construct($model, $title);
    $this->byteSize = $byteSize;
  }

  public function sqlCreateString(string $table, string $columnName): string
  {
    return "";
  }

}