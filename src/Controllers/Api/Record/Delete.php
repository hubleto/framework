<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Delete extends \Hubleto\Framework\Controllers\ApiController {

  public \Hubleto\Framework\Model $model;

  function __construct(\Hubleto\Framework\Loader $main, array $params = [])
  {
    parent::__construct($main, $params);

    $model = $this->main->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->main->getModel($model);
  }

  public function response(): array
  {
    $ok = false;
    $rowsAffected = 0;

    if ($this->main->config->getAsBool('encryptRecordIds')) {
      $hash = $this->main->urlParamAsString('hash');
      $ok = $hash == \Hubleto\Framework\Helper::encrypt($this->main->urlParamAsString('id'), '', true);
    } else {
      $id = $this->main->urlParamAsInteger('id');
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
        $errorHtml = $this->main->renderExceptionHtml($e, [$this->model]);
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
