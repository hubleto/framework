<?php

namespace Hubleto\Framework\Controllers\Api\Record;

use Hubleto\Framework\Controllers\CrudController;

class LoadTableData extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Interfaces\ModelInterface $model;

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
          return $crudControllerObj->loadTableData();
        } else {
          throw new \Exception('Invalid loader controller.');
        }
      } else {
        return $this->model->record->loadTableData(
          $this->router()->urlParamAsString('fulltextSearch'),
          $this->router()->urlParamAsArray('columnSearch'),
          $this->router()->urlParamAsArray('orderBy'),
          $this->router()->urlParamAsInteger('itemsPerPage', 15),
          $this->router()->urlParamAsInteger('page'),
          $this->router()->urlParamAsString('dataView'),
        );
      }
    } catch (\Throwable $e) {
      var_dump($e->getMessage());
      var_dump($e->getTraceAsString());exit;
    }
  }
}
