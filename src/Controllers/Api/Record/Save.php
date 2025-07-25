<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Save extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Model $model;

  function __construct(\Hubleto\Framework\Loader $main, array $params = []) {
    parent::__construct($main, $params);
    $model = $this->main->urlParamAsString('model');
    // $this->permission = $model . ':Create';
    $this->model = $this->main->getModel($model);
  }

  public function response(): array
  {
    $originalRecord = $this->main->urlParamAsArray('record');
    $modelClass = $this->main->urlParamAsString('model');

    if (empty($modelClass)) throw new \Exception("Master model is not specified.");

    $model = $this->main->getModel($modelClass);
    if (!is_object($model)) throw new \Exception("Unable to create model {$model}.");

    $savedRecord = $this->model->record->recordSave($originalRecord);

    return [
      'status' => 'success',
      'originalRecord' => $originalRecord,
      'savedRecord' => $savedRecord,
    ];
  }

}
