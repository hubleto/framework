<?php

namespace Hubleto\Framework\Controllers\Api\Record;

use Illuminate\Support\Str;

class GetList extends \Hubleto\Framework\Controllers\ApiController {
  public \Hubleto\Framework\Model $model;

  function __construct(\Hubleto\Framework\Loader $main, array $params = []) {
    parent::__construct($main, $params);

    $model = $this->getRouter()->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    return $this->model->recordGetList(
      $this->getRouter()->urlParamAsString('fulltextSearch'),
      $this->getRouter()->urlParamAsArray('columnSearch'),
      $this->getRouter()->urlParamAsArray('orderBy'),
      $this->getRouter()->urlParamAsInteger('itemsPerPage', 15),
      $this->getRouter()->urlParamAsInteger('page'),
    );
  }
}
