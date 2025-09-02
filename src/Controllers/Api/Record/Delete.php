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

      $error = '';
      $errorHtml = '';
      try {
        $this->model->onBeforeDelete((int) $id);
        $rowsAffected = $this->model->record->recordDelete($id);
        $this->model->onAfterDelete((int) $id);
      } catch (\Throwable $e) {
        $error = $e->getMessage();
        $errorHtml = $this->renderer()->renderExceptionHtml($e, [$this->model]);
      }

      $return = [
        'id' => $id,
        'status' => ($rowsAffected > 0),
      ];

      if ($error) $return['error'] = $error;
      if ($errorHtml) $return['errorHtml'] = $errorHtml;

      return $return;
    } else {
      return [
        'id' => $id,
        'status' => false,
      ];
    }
  }

}
