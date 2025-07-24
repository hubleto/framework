<?php

namespace Hubleto\Framework;

class AppTest implements \Hubleto\Legacy\Core\Testable
{
  public \HubletoMain\Loader $main;
  public \HubletoMain\Cli\Agent\Loader $cli;
  public \Hubleto\Framework\App $app;

  public function __construct(\Hubleto\Framework\App $app, \HubletoMain\Cli\Agent\Loader $cli)
  {
    $this->cli = $cli;
    $this->app = $app;
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

  public function assert(string $assertionName, bool $assertion): void
  {
    if ($this->main->testMode && !$assertion) {
      throw new Exceptions\TestAssertionFailedException('TEST FAILED: Assertion [' . $assertionName . '] not fulfilled in ' . get_parent_class($this));
    }
  }

}
