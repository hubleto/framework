<?php

namespace Hubleto\Framework\Db\Column;

class Virtual extends \Hubleto\Framework\Column
{

  protected string $type = 'virtual';
  protected string $searchAlgorithm = 'text';

  public function sqlCreateString(string $table, string $columnName): string
  {
    return "";
  }

}