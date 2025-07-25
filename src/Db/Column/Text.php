<?php

namespace Hubleto\Framework\Db\Column;

class Text extends \Hubleto\Framework\Db\Column
{

  protected string $type = 'text';
  protected string $sqlDataType = 'text';
  protected string $interface = 'plainText';

  public function __construct(\Hubleto\Framework\Model $model, string $title, string $interface = 'plainText')
  {
    parent::__construct($model, $title);
    $this->interface = $interface;
  }

  public function getInterface(): int { return $this->interface; }
  public function setInterface(int $interface): Decimal { $this->interface = $interface; return $this; }

  public function jsonSerialize(): array
  {
    $column = parent::jsonSerialize();
    $column['interface'] = $this->interface;
    return $column;
  }

}