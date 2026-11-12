<?php

namespace Hubleto\Framework\Controllers\Api\Tree;

class Describe extends \Hubleto\Framework\Controllers\ApiController
{

  public \Hubleto\Framework\Model $model;
  public bool $permittedForAllUsers = true;

  function __construct()
  {
    parent::__construct();

    $model = $this->router()->urlParamAsString('model');
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    return $this->model->describeTree()->toArray();
  }

}
