<?php

namespace Hubleto\Framework\Db\Column;

class PrimaryKey extends Integer
{

  protected string $rawSqlDefinition = 'primary key auto_increment';
  protected bool $readonly = true;
  protected string $searchAlgorithm = 'none';

  public function sqlIndexString(string $table, string $columnName): string
  {
    // The PRIMARY KEY already provides this index.
    return '';
  }

}