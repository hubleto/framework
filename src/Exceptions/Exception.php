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
    return [
      'status' => $this->status->toString(),
      'code' => (int) static::CODE,
      'message' => $this->getMessage(),
      'trace' => $this->getTraceAsString(),
      ...$this->getExtraParams(),
    ];
  }
}