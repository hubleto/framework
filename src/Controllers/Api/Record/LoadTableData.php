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

    $fulltextSearch = $this->router()->urlParamAsString('fulltextSearch');
    $columnSearch = $this->router()->urlParamAsArray('columnSearch');
    $orderBy = $this->router()->urlParamAsArray('orderBy');
    $itemsPerPage = $this->router()->urlParamAsInteger('itemsPerPage', 15);
    $page = $this->router()->urlParamAsInteger('page');
    $dataView = $this->router()->urlParamAsString('dataView');

    try {
      if (!empty($crudController)) {
        /** @var CrudController */
        $crudControllerObj = $this->getService($crudController);
        if (is_subclass_of($crudControllerObj, CrudController::class)) {
          return $crudControllerObj->loadTableData(
            $fulltextSearch,
            $columnSearch,
            $orderBy,
            $itemsPerPage,
            $page,
            $dataView,
          );
        } else {
          throw new \Exception('Invalid loader controller.');
        }
      } else {
        return $this->model->record->loadTableData(
          $fulltextSearch,
          $columnSearch,
          $orderBy,
          $itemsPerPage,
          $page,
          $dataView,
        );
      }
    } catch (\Throwable $e) {
      var_dump($e->getMessage());
      var_dump($e->getTraceAsString());exit;
    }
  }
}
