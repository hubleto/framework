<?php

namespace Hubleto\Framework;

class Test {
  public \Hubleto\Framework\Loader $main;

  public function __construct(\Hubleto\Framework\Loader $main)
  {
    $this->main = $main;
  }

}