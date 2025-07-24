<?php

namespace Hubleto\Legacy\Core\Db\Column;

class Json extends \Hubleto\Legacy\Core\Db\Column
{

  protected string $type = 'json';
  protected string $sqlDataType = 'text';

  public function __construct(\Hubleto\Legacy\Core\Model $model, string $title)
  {
    parent::__construct($model, $title);
  }

}