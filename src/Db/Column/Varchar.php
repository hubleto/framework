<?php

namespace Hubleto\Framework\Db\Column;

use \Hubleto\Framework\Db\ColumnProperty\Autocomplete;

class Varchar extends \Hubleto\Framework\Db\Column
{

  protected string $type = 'varchar';
  protected int $byteSize = 255;
  protected ?Autocomplete $autocomplete = null;

  public function __construct(\Hubleto\Framework\Model $model, string $title, int $byteSize = 255)
  {
    parent::__construct($model, $title);
    $this->byteSize = $byteSize;
  }

  public function getByteSize(): int { return $this->byteSize; }
  public function setByteSize(int $byteSize): Varchar { $this->byteSize = $byteSize; return $this; }

  public function getAutocomplete(): Autocomplete { return $this->autocomplete; }
  public function setAutocomplete(Autocomplete $autocomplete): Varchar { $this->autocomplete = $autocomplete; return $this; }

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
    if ($this->autocomplete !== null) $column['autocomplete'] = $this->autocomplete;
    return $column;
  }

  public function sqlCreateString(string $table, string $columnName): string
  {
    return "`{$columnName}` varchar($this->byteSize) " . $this->getRawSqlDefinition();
  }

}