<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Lookup extends \Hubleto\Framework\Controllers\ApiController {
  public bool $hideDefaultDesktop = true;

  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();

    $model = $this->router()->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    $search = $this->router()->urlParamAsString('search');
    $query = $this->model->record->prepareLookupQuery($search);

    $dataRaw = $query->get()->toArray();
    $data = [];

    if (is_array($dataRaw)) {
      $data = $this->model->record->prepareLookupData($dataRaw);
    }

    return \Hubleto\Framework\Helper::keyBy('id', $data);
  }

}
