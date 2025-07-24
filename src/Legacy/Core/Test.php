<?php

namespace Hubleto\Legacy\Core;

class Test {
  public \Hubleto\Legacy\Core\Loader $app;

  public function __construct(\Hubleto\Legacy\Core\Loader $app)
  {
    $this->app = $app;
  }

}