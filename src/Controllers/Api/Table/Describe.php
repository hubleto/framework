<?php

namespace Hubleto\Framework\Controllers\Api\Table;

use Hubleto\Framework\Controllers\CrudController;

class Describe extends \Hubleto\Framework\Controllers\ApiController {
  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();

    $model = $this->router()->urlParamAsString('model');

    if (!empty($model)) {
      $this->model = $this->getModel($model);
    }
  }

  public function response(): array
  {
    $crudController = $this->router()->urlParamAsString('crudController');

    try {
      if (!empty($crudController)) {
        /** @var CrudController */
        $crudControllerObj = $this->getService($crudController);
        if (is_subclass_of($crudControllerObj, CrudController::class)) {
          return $crudControllerObj->describeTable()->toArray();
        } else {
          throw new \Exception('Invalid loader controller.');
        }
      } else {
        return $this->model->describeTable()->toArray();
      }
    } catch (\Throwable $e) {
      var_dump($e->getMessage());
      var_dump($e->getTraceAsString());exit;
    }
  }

}
