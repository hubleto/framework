<?php

namespace Hubleto\Framework\Exceptions;

use Hubleto\Framework\Enums\ExceptionStatusEnum;

abstract class Exception extends \Exception
{

  public const int CODE = 1;

  private ExceptionStatusEnum $status = ExceptionStatusEnum::ERROR;

  public function getExtraParams(): array {
    return [];
  }

  public function getResponseArray(): array
  {
    http_response_code(400);
    return [
      'status' => $this->status->toString(),
      'code' => (int) static::CODE,
      'message' => $this->getMessage(),
      'trace' => $this->getTraceAsString(),
      'source' => 'hubleto-exception',
      ...$this->getExtraParams(),
    ];
  }
}