<?php

namespace Hubleto\Legacy\Core;

class ApiController extends \Hubleto\Legacy\Core\Controller {
  public int $returnType = self::RETURN_TYPE_JSON;

  public function response(): array
  {
    return [];
  }

  public function renderJson(): ?array {
    try {
      return $this->response();
    } catch (\Throwable $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
      ];
    }
  }
}

