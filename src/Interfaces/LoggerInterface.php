<?php

namespace Hubleto\Framework\Interfaces;

interface LoggerInterface
{

  public function initInternalLogger(string $loggerName = ""): void;
  public function getInternalLogger($loggerName): object;
  public function cliEcho($message, $loggerName, $severity): void;
  public function debug($message, array $context = [], $loggerName = 'core'): void;
  public function info($message, array $context = [], $loggerName = 'core'): void;
  public function warning($message, array $context = [], $loggerName = 'core'): void;
  public function error($message, array $context = [], $loggerName = 'core'): void;

}