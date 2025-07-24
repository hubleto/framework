<?php

namespace Hubleto\Legacy\Controllers\Api\Table;

class Describe extends \Hubleto\Legacy\Core\ApiController {
  public \Hubleto\Legacy\Core\Model $model;

  function __construct(\Hubleto\Legacy\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);

    $model = $this->app->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->app->getModel($model);
  }

  public function response(): array
  {
    return $this->model->describeTable()->toArray();
  }

}
