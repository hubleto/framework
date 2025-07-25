<?php

namespace Hubleto\Framework\Controllers\Api\Record;

use Illuminate\Support\Str;

class GetList extends \Hubleto\Framework\Controllers\ApiController {
  public \Hubleto\Framework\Model $model;

  function __construct(\Hubleto\Framework\Loader $main, array $params = []) {
    parent::__construct($main, $params);

    $model = $this->main->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->main->getModel($model);
  }

  public function response(): array
  {
    return $this->model->recordGetList(
      $this->main->urlParamAsString('fulltextSearch'),
      $this->main->urlParamAsArray('columnSearch'),
      $this->main->urlParamAsArray('orderBy'),
      $this->main->urlParamAsInteger('itemsPerPage', 15),
      $this->main->urlParamAsInteger('page'),
    );
  }
}
