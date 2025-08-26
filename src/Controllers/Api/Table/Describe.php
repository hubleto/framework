<?php

namespace Hubleto\Framework\Controllers\Api\Table;

class Describe extends \Hubleto\Framework\Controllers\ApiController {
  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();

    $model = $this->getRouter()->urlParamAsString('model');
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    return $this->model->describeTable()->toArray();
  }

}
