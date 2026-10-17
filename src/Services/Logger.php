<?php

namespace Hubleto\Framework\Services;

use Monolog\Logger as LoggerService;
use Monolog\Handler\RotatingFileHandler;
use Hubleto\Framework\Interfaces\LoggerInterface;
use Hubleto\Framework\Core;

/**
 * Default implementation of logger in Hubleto project.
 */
class Logger extends Core implements LoggerInterface {


  public array $loggers = [];

  public string $activeLogger = 'core';

  public bool $cliEchoEnabled = false;
  public string $logFolder = "";
  public bool $enabled = false;
  public int $debugLevel = self::DEBUG_LEVEL_NONE;

  private array $logCache = [];
 
  public function __construct()
  {
    parent::__construct();

    $this->logFolder = $this->config()->getAsString('logFolder');
    $this->enabled = !empty($this->logFolder) && is_dir($this->logFolder);
    $this->debugLevel = $this->config()->getAsInteger('debugLevel', self::DEBUG_LEVEL_NONE);

    $this->addLogger('core');
    $this->addLogger('cron');

    $this->setActiveLogger('core');
  }

  public function setActiveLogger(string $loggerName): void
  {
    if (!isset($this->loggers[$loggerName])) throw new \Exception('Logger ' . $loggerName . ' is not initialized.');
    $this->activeLogger = $loggerName;
  }

  /**
   * [Description for getActiveLoggerService]
   *
   * @return LoggerService
   * 
   */
  public function getActiveLoggerService(): LoggerService
  {
    return $this->loggers[$this->activeLogger];
  }

  public function setDebugLevel(int $level): void
  {
    $this->debugLevel = $level;
  }

  public function getDebugLevel(): int
  {
    return $this->debugLevel;
  }

  public function clearLogCache(): void
  {
    $this->logCache = [];
  }

  public function getLogCache(): array
  {
    return $this->logCache;
  }

  public function addLogger(string $loggerName): void
  {
    $this->loggers[$loggerName] = new LoggerService($loggerName);

    $debugStreamHandler = new RotatingFileHandler("{$this->logFolder}/debug.log", 1000, LoggerService::DEBUG);
    $debugStreamHandler->setFilenameFormat($loggerName . '/{date}/{filename}', 'Y/m/d');
    $this->loggers[$loggerName]->pushHandler($debugStreamHandler);

    $infoStreamHandler = new RotatingFileHandler("{$this->logFolder}/info.log", 1000, LoggerService::INFO);
    $infoStreamHandler->setFilenameFormat($loggerName . '/{date}/{filename}', 'Y/m/d');
    $this->loggers[$loggerName]->pushHandler($infoStreamHandler);

    $warningStreamHandler = new RotatingFileHandler("{$this->logFolder}/warning.log", 1000, LoggerService::WARNING);
    $warningStreamHandler->setFilenameFormat($loggerName . '/{date}/{filename}', 'Y/m/d');
    $this->loggers[$loggerName]->pushHandler($warningStreamHandler);

    $errorStreamHandler = new RotatingFileHandler("{$this->logFolder}/error.log", 1000, LoggerService::ERROR);
    $errorStreamHandler->setFilenameFormat($loggerName . '/{date}/{filename}', 'Y/m/d');
    $this->loggers[$loggerName]->pushHandler($errorStreamHandler);

  }
  
  /**
   * [Description for cliEcho]
   *
   * @param mixed $message
   * @param mixed $loggerName
   * @param mixed $severity
   * 
   * @return void
   * 
   */
  public function cliEcho(string $message, string $severity): void
  {
    if ($this->cliEchoEnabled && php_sapi_name() === 'cli') {
      echo date("Y-m-d H:i:s")." {$this->activeLogger}.{$severity} {$message}\n";
    }
  }

  /**
   * [Description for debug]
   *
   * @param mixed $message
   * @param array $context
   * 
   * @return void
   * 
   */
  public function debug(string $message, array $context = []): void
  {
    if (!$this->enabled) return;
    if ($this->debugLevel < self::DEBUG_LEVEL_DEBUG) return;
    $this->getActiveLoggerService()->debug($message, $context);
    $this->cliEcho($message, 'DEBUG');
  }
  
  /**
   * [Description for info]
   *
   * @param mixed $message
   * @param array $context
   * 
   * @return void
   * 
   */
  public function info(string $message, array $context = []): void
  {
    if (!$this->enabled) return;
    if ($this->debugLevel < self::DEBUG_LEVEL_INFO) return;
    $this->logCache[] = '[INFO] ' . $message;
    $this->getActiveLoggerService()->info($message, $context);
    $this->cliEcho($message, 'INFO');
  }
  
  /**
   * [Description for warning]
   *
   * @param mixed $message
   * @param array $context
   * 
   * @return void
   * 
   */
  public function warning(string $message, array $context = []): void
  {
    if (!$this->enabled) return;
    if ($this->debugLevel < self::DEBUG_LEVEL_WARNING) return;
    $this->logCache[] = '[WARNING] ' . $message;
    $this->getActiveLoggerService()->warning($message, $context);
    $this->cliEcho($message, 'WARNING');
  }
  
  /**
   * [Description for error]
   *
   * @param mixed $message
   * @param array $context
   * 
   * @return void
   * 
   */
  public function error(string $message, array $context = []): void
  {
    if (!$this->enabled) return;
    if ($this->debugLevel < self::DEBUG_LEVEL_ERROR) return;
    $this->logCache[] = '[ERROR] ' . $message;
    $this->getActiveLoggerService()->error($message, $context);
    $this->cliEcho($message, 'ERROR');
  }

}