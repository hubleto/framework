<?php

namespace Hubleto\Framework\Db\Column;

class Email extends Varchar
{
  //protected string $type = 'email';
  public function validate(mixed $value): bool
  {
    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function normalize(mixed $value): mixed
  {
    $value = trim($value);
    $value = strtolower($value);
    return parent::normalize($value);
  }

}