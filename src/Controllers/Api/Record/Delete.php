<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Delete extends \Hubleto\Framework\Controllers\ApiController {

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
    $ok = false;
    $rowsAffected = 0;

    if ($this->config()->getAsBool('encryptRecordIds')) {
      $hash = $this->router()->urlParamAsString('hash');
      $ok = $hash == \Hubleto\Framework\Helper::encrypt($this->router()->urlParamAsString('id'), '', true);
    } else {
      $id = $this->router()->urlParamAsInteger('id');
      $ok = $id > 0;
    }

    if ($ok) {

      try {
        $this->model->onBeforeDelete((int) $id);
        $rowsAffected = $this->model->record->recordDelete($id);
        $this->model->onAfterDelete((int) $id);
      } catch (\Illuminate\Database\QueryException $e) {
        $dbQuery = $e->getSql();
        $dbError = $e->errorInfo[2];
        $errorNo = $e->errorInfo[1];

        if (in_array($errorNo, [1216, 1451])) {
          $message =
            $this->translate('{{ modelName }} cannot be deleted, other data is linked to it.', ['modelName' => $this->model->shortName], 'hubleto-erp-loader:Renderer')
          ;
        } elseif (in_array($errorNo, [1062, 1217, 1452])) {
          $message =
            $this->translate('{{ modelName }} cannot be saved, duplicate data detected.', ['modelName' => $this->model->shortName], 'hubleto-erp-loader:Renderer')
          ;
        } else {
          $message = $this->translate('Unknown database error occured.');
        }

        throw new \Exception($message);
      }

      $return = [
        'id' => $id,
        'status' => ($rowsAffected > 0),
      ];

      return $return;
    } else {
      return [
        'id' => $id,
        'status' => false,
      ];
    }
  }

}
