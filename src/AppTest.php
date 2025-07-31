<?php

namespace Hubleto\Framework;

class AppTest
{
  public \Hubleto\Framework\Loader $main;

  public function __construct(public \Hubleto\Framework\App $app, public \HubletoMain\Cli\Agent\Loader $cli)
  {
    $this->main = $app->main;
  }

  public function run(): void
  {
    // Throw exception if test fails
  }

  /** @return array<string> */
  public function sqlInjectionExpressions(): array
  {
    return [
      '\'',
    ];
  }

}
