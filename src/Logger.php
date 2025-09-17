<?php

namespace Hubleto\Framework;

use Monolog\Handler\RotatingFileHandler;

/**
 * Default implementation of logger in Hubleto project.
 */
class Logger extends Core implements Interfaces\LoggerInterface {

  public array $loggers = [];

  public bool $cliEchoEnabled = false;
  public string $logFolder = "";
  public bool $enabled = false;
 
  public function __construct()
  {
    parent::__construct();

    $this->logFolder = $this->config()->getAsString('logFolder');
    $this->enabled = !empty($this->logFolder) && is_dir($this->logFolder);

    $this->initInternalLogger('core');
  }

  /**
   * [Description for initInternalLogger]
   *
   * @param string $loggerName
   * 
   * @return void
   * 
   */
  public function initInternalLogger(string $loggerName = ""): void
  {
    if (!class_exists("\\Monolog\\Logger")) return;

    // inicializacia loggerov
    $this->loggers[$loggerName] = new \Monolog\Logger($loggerName);

    $debugStreamHandler = new RotatingFileHandler("{$this->logFolder}/{$loggerName}-debug.log", 1000, \Monolog\Logger::DEBUG);
    $debugStreamHandler->setFilenameFormat('{date}/{filename}', 'Y/m/d');

    $infoStreamHandler = new RotatingFileHandler("{$this->logFolder}/{$loggerName}-info.log", 1000, \Monolog\Logger::INFO);
    $infoStreamHandler->setFilenameFormat('{date}/{filename}', 'Y/m/d');

    $warningStreamHandler = new RotatingFileHandler("{$this->logFolder}/{$loggerName}-warning.log", 1000, \Monolog\Logger::WARNING);
    $warningStreamHandler->setFilenameFormat('{date}/{filename}', 'Y/m/d');

    $errorStreamHandler = new RotatingFileHandler("{$this->logFolder}/{$loggerName}-error.log", 1000, \Monolog\Logger::ERROR);
    $errorStreamHandler->setFilenameFormat('{date}/{filename}', 'Y/m/d');

    $this->loggers[$loggerName]->pushHandler($debugStreamHandler);
    $this->loggers[$loggerName]->pushHandler($infoStreamHandler);
    $this->loggers[$loggerName]->pushHandler($warningStreamHandler);
    $this->loggers[$loggerName]->pushHandler($errorStreamHandler);

  }
  
  /**
   * [Description for getInternalLogger]
   *
   * @param mixed $loggerName
   * 
   * @return object
   * 
   */
  public function getInternalLogger($loggerName): object
  {
    if (!isset($this->loggers[$loggerName])) {
      $this->initInternalLogger($loggerName);
    }

    return $this->loggers[$loggerName];
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
  public function cliEcho($message, $loggerName, $severity): void
  {
    if ($this->cliEchoEnabled && php_sapi_name() === 'cli') {
      echo date("Y-m-d H:i:s")." {$loggerName}.{$severity} {$message}\n";
    }
  }

  /**
   * [Description for debug]
   *
   * @param mixed $message
   * @param array $context
   * @param string $loggerName
   * 
   * @return void
   * 
   */
  public function debug($message, array $context = [], $loggerName = 'core'): void
  {
    if (!$this->enabled) return;
    $this->getInternalLogger($loggerName)->debug($message, $context);
    $this->cliEcho($message, $loggerName, 'DEBUG');
  }
  
  /**
   * [Description for info]
   *
   * @param mixed $message
   * @param array $context
   * @param string $loggerName
   * 
   * @return void
   * 
   */
  public function info($message, array $context = [], $loggerName = 'core'): void
  {
    if (!$this->enabled) return;
    $this->getInternalLogger($loggerName)->info($message, $context);
    $this->cliEcho($message, $loggerName, 'INFO');
  }
  
  /**
   * [Description for warning]
   *
   * @param mixed $message
   * @param array $context
   * @param string $loggerName
   * 
   * @return void
   * 
   */
  public function warning($message, array $context = [], $loggerName = 'core'): void
  {
    if (!$this->enabled) return;
    $this->getInternalLogger($loggerName)->warning($message, $context);
    $this->cliEcho($message, $loggerName, 'WARNING');
  }
  
  /**
   * [Description for error]
   *
   * @param mixed $message
   * @param array $context
   * @param string $loggerName
   * 
   * @return void
   * 
   */
  public function error($message, array $context = [], $loggerName = 'core'): void
  {
    if (!$this->enabled) return;
    $this->getInternalLogger($loggerName)->error($message, $context);
    $this->cliEcho($message, $loggerName, 'ERROR');
  }

}