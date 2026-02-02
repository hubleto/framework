<?php

namespace Hubleto\Framework\Db\Column;

class Varchar extends \Hubleto\Framework\Column
{

  protected string $type = 'varchar';
  protected int $byteSize = 255;
  protected string $searchAlgorithm = 'text';

  public function sqlCreateString(string $table, string $columnName): string
  {
    return "`{$columnName}` varchar($this->byteSize) " . $this->getRawSqlDefinition();
  }

}