<?php

namespace Hubleto\Framework\Services;

use Hubleto\Framework\Interfaces\NotifierInterface;

class Notifier implements NotifierInterface
{

  public function notify(string $to, string $message): void
  {
    // default notifier service does nothing
  }

}
