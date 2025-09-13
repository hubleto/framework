<?php

namespace Hubleto\Framework\Db\Column;

class Decimal extends \Hubleto\Framework\Column
{

  protected string $type = 'float';
  protected string $sqlDataType = 'decimal';

  public function normalize(mixed $value): mixed
  {
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