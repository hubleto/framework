<?php

namespace Hubleto\Framework\Db\Column;

use DateTime as DT;

class DateTime extends \Hubleto\Framework\Column
{

  protected string $type = 'datetime';
  protected string $sqlDataType = 'datetime';
  protected string $searchAlgorithm = 'datetime';
  protected string $textAlign = 'right';

  public function normalize(mixed $value): mixed
  {
    $date = new DT((string) $value);
    $dateFormated = $date->format("Y-m-d:H:i:s");
    return $dateFormated;
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}