<?php

namespace Hubleto\Framework\Db\Column;

class DateTime extends \Hubleto\Framework\Column
{

  protected string $type = 'datetime';
  protected string $sqlDataType = 'datetime';
  protected string $searchAlgorithm = 'datetime';

  public function normalize(mixed $value): mixed
  {
    return strtotime((string) $value) < 1000 ? null : $value;
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}