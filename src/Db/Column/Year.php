<?php

namespace Hubleto\Framework\Db\Column;

class Year extends \Hubleto\Framework\Column
{

  protected string $type = 'year';
  protected string $sqlDataType = 'year';
  protected string $searchAlgorithm = 'number';

  public function normalize(mixed $value): mixed
  {
    return (int) $value;
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}