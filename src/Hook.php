<?php

namespace HubletoMain\Core;

class Hook
{
  public \HubletoMain\Loader $main;

  public function __construct(\HubletoMain\Loader $main)
  {
    $this->main = $main;
  }

  public function run(string $event, array $args): void
  {
    // to be overriden
  }

}
