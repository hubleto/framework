<?php

namespace HubletoMain\Core;

class Cron
{
  // CRON-formatted string specifying the scheduling pattern
  public string $schedulingPattern = '* * * * *';

  public \HubletoMain\Loader $main;

  public function __construct(\HubletoMain\Loader $main)
  {
    $this->main = $main;
  }

  public function run(): void
  {
    // to be overriden
  }

}
