<?php

namespace Hubleto\Framework\Controllers\Api\Record;

class Lookup extends \Hubleto\Framework\Controllers\ApiController {
  public bool $hideDefaultDesktop = true;
  public bool $permittedForAllUsers = true;

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

    // One cache per model, the same key the model clears when a record is saved or deleted.
    $cacheKey = 'cache/lookup/' . $this->model->fullName;

    // A search is never cached, it would fill the cache with an answer for every typed text.
    $useCache = empty($search);

    // The answer depends on the user (read scope) and on the other parameters (form record, filters of the app),
    // so each combination is cached separately.
    $cacheParams = $this->router()->getUrlParams();
    unset($cacheParams['model']);
    $cacheParams['idUser'] = $this->authProvider()->getUserId();
    $cacheParamsHash = md5((string) json_encode($cacheParams));

    $dataFromCache = false;
    $data = [];

    if ($useCache) {
      $cachedAnswers = $this->config()->getAsJson($cacheKey);
      $cachedAnswer = $cachedAnswers[$cacheParamsHash] ?? null;
      if ($this->isCachedAnswerValid($cachedAnswer)) {
        $data = $cachedAnswer['data'];
        $dataFromCache = true;
      }
    }

    if (!$dataFromCache) {
      $query = $this->model->record->prepareLookupQuery($search);
      $dataRaw = $query->get()->toArray();

      if (is_array($dataRaw)) {
        $data = $this->model->record->prepareLookupData($dataRaw);
      }

      if ($useCache) {
        // Expired answers of other parameters are dropped, so the cache does not grow.
        $validAnswers = [];
        foreach ($this->config()->getAsJson($cacheKey) as $paramsHash => $cachedAnswer) {
          if ($this->isCachedAnswerValid($cachedAnswer)) {
            $validAnswers[$paramsHash] = $cachedAnswer;
          }
        }

        $validAnswers[$cacheParamsHash] = [
          'expiry' => time() + 60*5,
          'data' => $data
        ];
        $this->config()->save($cacheKey, json_encode($validAnswers));
      }
    }

    return $data;
  }

  // A cleared cache of the model is stored as {"expiry":0,"data":[]}, so its items are not valid answers.
  private function isCachedAnswerValid(mixed $cachedAnswer): bool
  {
    if (!is_array($cachedAnswer)) return false;
    if (!isset($cachedAnswer['expiry'], $cachedAnswer['data'])) return false;
    return $cachedAnswer['expiry'] > time();
  }

}
