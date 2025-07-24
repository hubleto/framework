<?php

namespace Hubleto\Legacy\Controllers\Api\Record;

class Lookup extends \Hubleto\Legacy\Core\ApiController {
  public bool $hideDefaultDesktop = true;

  public \Hubleto\Legacy\Core\Model $model;

  function __construct(\Hubleto\Legacy\Core\Loader $app, array $params = []) {
    parent::__construct($app, $params);

    $model = $this->app->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->app->getModel($model);
  }

  public function response(): array
  {
    $search = $this->app->urlParamAsString('search');
    $query = $this->model->record->prepareLookupQuery($search);

    $dataRaw = $query->get()->toArray();
    $data = [];

    if (is_array($dataRaw)) {
      $data = $this->model->record->prepareLookupData($dataRaw);
    }

    return \Hubleto\Legacy\Core\Helper::keyBy('id', $data);
  }

}
