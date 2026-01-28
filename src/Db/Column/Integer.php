<?php

namespace Hubleto\Framework\Db\Column;

class Integer extends \Hubleto\Framework\Column
{

  protected string $type = 'int';
  protected int $byteSize = 255;

  public function __construct(\Hubleto\Framework\Model $model, string $title, int $byteSize = 255)
  {
    parent::__construct($model, $title);
    $this->byteSize = $byteSize;
  }

  public function getByteSize(): int { return $this->byteSize; }
  public function setByteSize(int $byteSize): Integer { $this->byteSize = $byteSize; return $this; }

  public function describeInput(): \Hubleto\Framework\Description\Input
  {
    $description = parent::describeInput();
    if (!empty($this->getEnumValues())) $description->setEnumValues($this->getEnumValues());
    return $description;
  }

  public function jsonSerialize(): array
  {
    $column = parent::jsonSerialize();
    $column['byteSize'] = $this->byteSize;
    return $column;
  }

  public function getNullValue(): mixed
  {
    return 0;
  }
  
  public function normalize(mixed $value): mixed
  {
    if ($value === null) return null;
    return (int) $value;
  }

  public function validate(mixed $value): bool
  {
    return empty($value) || is_numeric($value);
  }

  public function sqlCreateString(string $table, string $columnName): string
  {
    return "`{$columnName}` int($this->byteSize) " . $this->getRawSqlDefinition();
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}