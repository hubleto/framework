<?php

namespace Hubleto\Framework;

class CustomRenderer
{
  public function render(string $view, array $params): string
  {
    return print_r($view, true);
  }
}
