<?php

namespace Hubleto\Framework\Interfaces;

interface TestableInterface {
  public function assert(string $assertionName, bool $assertion);
}