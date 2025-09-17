<?php

namespace Hubleto\Framework\Controllers\Api\Record;

use Illuminate\Support\Str;

class GetList extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Interfaces\ModelInterface $model;

  function __construct()
  {
    parent::__construct();

    $model = $this->router()->urlParamAsString('model');
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    return $this->model->recordGetList(
      $this->router()->urlParamAsString('fulltextSearch'),
      $this->router()->urlParamAsArray('columnSearch'),
      $this->router()->urlParamAsArray('orderBy'),
      $this->router()->urlParamAsInteger('itemsPerPage', 15),
      $this->router()->urlParamAsInteger('page'),
    );
  }
}
