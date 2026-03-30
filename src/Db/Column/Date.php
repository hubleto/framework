<?php

namespace Hubleto\Framework\Db\Column;

use DateTime;

class Date extends \Hubleto\Framework\Column
{

  protected string $type = 'date';
  protected string $sqlDataType = 'date';
  protected string $searchAlgorithm = 'date';
  protected string $textAlign = 'right';

  public function normalize(mixed $value): mixed
  {
    $date = new DateTime((string) $value);
    $dateFormated = $date->format("Y-m-d");
    return $dateFormated;
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}