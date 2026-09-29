<?php

namespace Hubleto\Framework\Interfaces;

interface NotifierInterface
{

  public function notify(string $to, string $message): void;

}
