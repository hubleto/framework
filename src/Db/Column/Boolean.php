<?php

namespace Hubleto\Framework\Db\Column;

class Boolean extends \Hubleto\Framework\Db\Column
{

  protected string $type = 'boolean';
  protected string $sqlDataType = 'int(1)';
  protected mixed $yesValue = true;
  protected mixed $noValue = false;

  public function getYesValue(): mixed { return $this->yesValue; }
  public function setYesValue(mixed $yesValue): \Hubleto\Framework\Db\Column\Boolean { $this->yesValue = $yesValue; return $this; }

  public function getNoValue(): mixed { return $this->noValue; }
  public function setNoValue(mixed $noValue): \Hubleto\Framework\Db\Column\Boolean { $this->noValue = $noValue; return $this; }

  public function __construct(\Hubleto\Framework\Model $model, string $title)
  {
    parent::__construct($model, $title);
  }

  public function normalize(mixed $value): mixed
  {
    if (empty($value) || !((bool) $value) || $value === $this->getNoValue()) {
      return $this->getNoValue();
    } else {
      return $this->getYesValue();
    }
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}