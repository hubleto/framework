<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Get extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();

    $model = $this->router()->urlParamAsString('model');
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    $record = [];

    $idEncrypted = $this->router()->urlParamAsString('id');
    $id = (int) \Hubleto\Framework\Helper::decrypt($idEncrypted);

    if ($id > 0) {
      $record = $this->model->recordGet(
        function($q) use ($id) { $q->where($this->model->table . '.id', $id); }
      );
    }

    return $record;
  }

}
