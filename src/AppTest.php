<?php

namespace Hubleto\Framework;

class AppTest implements \Hubleto\Framework\Interfaces\TestableInterface
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

  public function assert(string $assertionName, bool $assertion): void
  {
    if ($this->main->testMode && !$assertion) {
      throw new Exceptions\TestAssertionFailedException('TEST FAILED: Assertion [' . $assertionName . '] not fulfilled in ' . get_parent_class($this));
    }
  }

}
