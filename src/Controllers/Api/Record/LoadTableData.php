<?php

namespace Hubleto\Framework\Controllers\Api\Record;

use Illuminate\Support\Str;

class LoadTableData extends \Hubleto\Erp\Controllers\ApiController {

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
      return $this->model->record->loadTableData(
        $this->router()->urlParamAsString('fulltextSearch'),
        $this->router()->urlParamAsArray('columnSearch'),
        $this->router()->urlParamAsArray('orderBy'),
        $this->router()->urlParamAsInteger('itemsPerPage', 15),
        $this->router()->urlParamAsInteger('page'),
        $this->router()->urlParamAsString('dataView'),
      );
    } catch (\Throwable $e) {
      var_dump($e->getMessage());
      var_dump($e->getTraceAsString());exit;
    }
  }
}
