<?php

namespace Hubleto\Framework\Controllers\Api\Record;

use Illuminate\Support\Str;

class LoadTreeData extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Interfaces\ModelInterface $model;

  function __construct()
  {
    parent::__construct();

    $model = $this->router()->urlParamAsString('model');
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    try {
      return $this->model->record->loadTreeData(
        $this->router()->urlParamAsString('fulltextSearch'),
        $this->router()->urlParamAsArray('orderBy'),
      );
    } catch (\Throwable $e) {
      var_dump($e->getMessage());
      var_dump($e->getTraceAsString());exit;
    }
  }
}
