<?php

namespace Hubleto\Framework\Interfaces;

interface LoggerInterface extends CoreInterface
{

  public function addLogger(string $loggerName): void;
  public function setActiveLogger(string $loggerName): void;
  public function getActiveLoggerService(): object;
  public function cliEcho(string $message, string $severity): void;
  public function debug(string $message, array $context = []): void;
  public function info(string $message, array $context = []): void;
  public function warning(string $message, array $context = []): void;
  public function error(string $message, array $context = []): void;
  public function clearLogCache(): void;
  public function getLogCache(): array;
  public function setDebugLevel(int $level): void;
  public function getDebugLevel(): int;

}