<?php

namespace Hubleto\Framework\Db\Column;

class Decimal extends \Hubleto\Framework\Column
{

  protected string $type = 'decimal';
  protected string $sqlDataType = 'decimal';
  protected string $searchAlgorithm = 'number';
  protected string $textAlign = 'right';
  protected int $decimals = 4;

  public function normalize(mixed $value): mixed
  {
    if ($value === null) return null;
    return (float) $value;
  }

  public function validate(mixed $value): bool
  {
    return empty($value) || is_numeric($value);
  }

  public function sqlCreateString(string $table, string $columnName): string
  {
    return (empty($this->sqlDataType) ? '' : "`{$columnName}` {$this->sqlDataType}($this->byteSize, $this->decimals) " . $this->getRawSqlDefinition());
  }

}