<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Save extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();
    $model = $this->router()->urlParamAsString('model');
    // $this->permission = $model . ':Create';
    $this->model = $this->getModel($model);
  }

  public function response(): array
  {
    $record = $this->router()->urlParamAsArray('record');
    $modelClass = $this->router()->urlParamAsString('model');
    $saveRelations = $this->router()->urlParamAsArray('saveRelations');
    $originalRecord = [];

    $idRecord = (int) ($record['id'] ?? 0);

    if (empty($modelClass)) throw new \Exception("Master model is not specified.");

    $model = $this->getModel($modelClass);

    if ($idRecord > 0) {
      $originalRecord = $model->record->find($idRecord)->toArray();
    } else {
      $originalRecord = $record;
    }

    if (!is_object($model)) throw new \Exception("Unable to create model {$model}.");

    $savedRecord = $this->model->record->recordSave(
      $record,
      0, // $idMasterRecord
      $saveRelations
    );

    return [
      'status' => 'success',
      'originalRecord' => $originalRecord,
      'savedRecord' => $savedRecord,
    ];
  }

}
