<?php

namespace Hubleto\Framework\Controllers\Api\Form;

class Describe extends \Hubleto\Framework\Controllers\ApiController {
  public \Hubleto\Framework\Model $model;

  function __construct(\Hubleto\Framework\Loader $main, array $params = [])
  {
    parent::__construct($main, $params);

    $model = $this->main->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->main->getModel($model);
  }

  public function response(): array
  {
    return $this->model->describeForm()->toArray();
  }
}
