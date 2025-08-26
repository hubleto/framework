<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Save extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();
    $model = $this->getRouter()->urlParamAsString('model');
    // $this->permission = $model . ':Create';
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    $originalRecord = $this->getRouter()->urlParamAsArray('record');
    $modelClass = $this->getRouter()->urlParamAsString('model');
    $saveRelationsRecursively = $this->getRouter()->urlParamAsBool('saveRecursively');

    if (empty($modelClass)) throw new \Exception("Master model is not specified.");

    $model = $this->getModel($modelClass);
    if (!is_object($model)) throw new \Exception("Unable to create model {$model}.");

    $savedRecord = $this->model->record->recordSave(
      $originalRecord,
      0, // $idMasterRecord
      $saveRelationsRecursively
    );

    return [
      'status' => 'success',
      'originalRecord' => $originalRecord,
      'savedRecord' => $savedRecord,
    ];
  }

}
