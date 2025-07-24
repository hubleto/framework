<?php

namespace Hubleto\Framework;

class Hook
{
  public \Hubleto\Framework\Loader $main;

  public function __construct(\Hubleto\Framework\Loader $main)
  {
    $this->main = $main;
  }

  public function run(string $event, array $args): void
  {
    // to be overriden
  }

}
