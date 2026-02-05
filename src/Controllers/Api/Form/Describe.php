<?php

namespace Hubleto\Framework\Controllers\Api\Form;

class Describe extends \Hubleto\Erp\Controllers\ApiController {
  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();

    $model = $this->router()->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    return $this->model->describeForm()->toArray();
  }
}
