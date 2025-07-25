<?php

namespace Hubleto\Framework;

interface Testable {
  public function assert(string $assertionName, bool $assertion);
}