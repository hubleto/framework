<?php

namespace Hubleto\Framework;

use Monolog\Handler\RotatingFileHandler;

/**
 * Debugger console.
 */
class Logger extends Core {

  public array $loggers = [];

  public bool $cliEchoEnabled = false;
  public string $logFolder = "";
  public bool $enabled = false;
 
  public function __construct()
  {
    parent::__construct();

    $this->logFolder = $this->getConfig()->getAsString('logFolder');
    $this->enabled = !empty($this->logFolder) && is_dir($this->logFolder);

    $this->initInternalLogger('core');
  }

  public function initInternalLogger(string $loggerName = "") {
    if (!class_exists("\\Monolog\\Logger")) return;

    // inicializacia loggerov
    $this->loggers[$loggerName] = new \Monolog\Logger($loggerName);
    $infoStreamHandler = new RotatingFileHandler("{$this->logFolder}/{$loggerName}-info.log", 1000, \Monolog\Logger::INFO);
    $infoStreamHandler->setFilenameFormat('{date}/{filename}', 'Y/m/d');

    $warningStreamHandler = new RotatingFileHandler("{$this->logFolder}/{$loggerName}-warning.log", 1000, \Monolog\Logger::WARNING);
    $warningStreamHandler->setFilenameFormat('{date}/{filename}', 'Y/m/d');

    $errorStreamHandler = new RotatingFileHandler("{$this->logFolder}/{$loggerName}-error.log", 1000, \Monolog\Logger::ERROR);
    $errorStreamHandler->setFilenameFormat('{date}/{filename}', 'Y/m/d');

    $this->loggers[$loggerName]->pushHandler($infoStreamHandler);
    $this->loggers[$loggerName]->pushHandler($warningStreamHandler);
    $this->loggers[$loggerName]->pushHandler($errorStreamHandler);

  }
  
  public function getInternalLogger($loggerName) {
    if (!isset($this->loggers[$loggerName])) {
      $this->initInternalLogger($loggerName);
    }

    return $this->loggers[$loggerName];
  }

  public function cliEcho($message, $loggerName, $severity) {
    if ($this->cliEchoEnabled && php_sapi_name() === 'cli') {
      echo date("Y-m-d H:i:s")." {$loggerName}.{$severity} {$message}\n";
    }
  }

  public function info($message, array $context = [], $loggerName = 'core') {
    if (!$this->enabled) return;
    $this->getInternalLogger($loggerName)->info($message, $context);
    $this->cliEcho($message, $loggerName, 'INFO');
  }
  
  public function warning($message, array $context = [], $loggerName = 'core') {
    if (!$this->enabled) return;
    $this->getInternalLogger($loggerName)->warning($message, $context);
    $this->cliEcho($message, $loggerName, 'WARNING');
  }
  
  public function error($message, array $context = [], $loggerName = 'core') {
    if (!$this->enabled) return;
    $this->getInternalLogger($loggerName)->error($message, $context);
    $this->cliEcho($message, $loggerName, 'ERROR');
  }

}