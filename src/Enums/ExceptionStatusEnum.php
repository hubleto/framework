<?php

namespace Hubleto\Framework\Enums;

enum ExceptionStatusEnum
{
  case ERROR;
  case SUCCESS;

  public function toString(): string
  {
    return match ($this) {
      ExceptionStatusEnum::ERROR => 'error',
      ExceptionStatusEnum::SUCCESS => 'success',
    };
  }
}
