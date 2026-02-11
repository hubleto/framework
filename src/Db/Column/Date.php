<?php

namespace Hubleto\Framework\Db\Column;

class Date extends \Hubleto\Framework\Column
{

  protected string $type = 'date';
  protected string $sqlDataType = 'date';
  protected string $searchAlgorithm = 'date';
  protected string $textAlign = 'right';

  public function normalize(mixed $value): mixed
  {
    return strtotime((string) $value) < 1000 ? null : $value;
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}