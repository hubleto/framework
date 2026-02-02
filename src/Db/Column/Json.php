<?php

namespace Hubleto\Framework\Db\Column;

class Json extends \Hubleto\Framework\Column
{

  protected string $type = 'json';
  protected string $sqlDataType = 'text';
  protected string $searchAlgorithm = 'text';

  public function __construct(\Hubleto\Framework\Model $model, string $title)
  {
    parent::__construct($model, $title);
  }

}