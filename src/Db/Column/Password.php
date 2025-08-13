<?php

namespace Hubleto\Framework\Db\Column;

class Password extends \Hubleto\Framework\Db\Column\Varchar
{

  protected string $type = 'password';
  protected bool $hidden = true;

  public function normalize(mixed $value): mixed
  {
    if (is_array($value)) {
      return $this->model->encryptPassword((string) $value[0]);
    } else {
      return (string) $value;
    }
  }

  public function validate($value): bool
  {
    if (is_array($value)) {
      return $value[0] == $value[1];
    } else {
      return true;
    }
  }

}