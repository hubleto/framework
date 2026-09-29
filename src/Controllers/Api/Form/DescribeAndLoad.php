<?php

namespace Hubleto\Framework\Controllers\Api\Form;

class DescribeAndLoad extends \Hubleto\Framework\Controllers\ApiController {
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
    try {
      $descripiton = $this->model->describeForm()->toArray();

      $record = [];

      $idEncrypted = $this->router()->urlParamAsString('id');
      $id = (int) \Hubleto\Framework\Helper::decrypt($idEncrypted);

      if ($id > 0) {
        $record = $this->model->record->loadFormData($id);
      }

      return [
        "description" => $descripiton,
        "record" => $record,
      ];
    } catch (\Throwable $e) {
      var_dump($e->getMessage());
      var_dump($e->getTraceAsString());exit;
    }

  }
}
