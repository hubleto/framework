<?php

namespace Hubleto\Framework\Db\Column;

class Virtual extends \Hubleto\Framework\Column
{

  protected string $type = 'virtual';

  public function sqlCreateString(string $table, string $columnName): string
  {
    return "";
  }

}