<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Lookup extends \Hubleto\Framework\Controllers\ApiController {
  public bool $hideDefaultDesktop = true;

  public string $modelClass = '';
  public \Hubleto\Framework\Model $model;

  function __construct()
  {
    parent::__construct();

    $this->modelClass = $this->router()->urlParamAsString('model');
    // $this->permission = $model . ':Read';
    $this->model = $this->getModel($this->modelClass);
  }

  public function response(): array
  {
    $search = $this->router()->urlParamAsString('search');

    $dataFromCache = false;
    $data = [];

    if (empty($search)) {
      $cache = $this->config()->getAsJson('cache/lookup/' . $this->modelClass);
      if (is_array($cache) && isset($cache['expiry']) && $cache['expiry'] > time()) {
        $data = $cache['data'];
        $dataFromCache = true;
      }
    }

    if (!$dataFromCache) {
      $query = $this->model->record->prepareLookupQuery($search);
      $dataRaw = $query->get()->toArray();

      if (is_array($dataRaw)) {
        $data = $this->model->record->prepareLookupData($dataRaw);
      }

      $this->config()->save('cache/lookup/' . $this->modelClass, json_encode([
        'expiry' => time() + 60*5,
        'data' => $data
      ]));
    }

    return $data;
  }

}
