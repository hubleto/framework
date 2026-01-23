<?php

namespace Hubleto\Framework;

register_shutdown_function(function() {
  $error = error_get_last();
  if ($error !== null && $error['type'] == E_ERROR) {
    header('HTTP/1.1 400 Bad Request', true, 400);
  }
});

/**
 * Default implementation of bootstrap loader.
 */
class Loader extends Core
{

  const RELATIVE_DICTIONARY_PATH = '../lang';

  public function __construct(array $config = [])
  {
    parent::__construct();

    $this->setAsGlobal();

    try {
      $this->config()->setConfig($config);

    } catch (\Exception $e) {
      echo "Hubleto boot failed: [".get_class($e)."] ".$e->getMessage() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }

  }

  /**
   * Set $this as the global instance of Hubleto.
   *
   * @return void
   * 
   */
  public function setAsGlobal(): void
  {
    $GLOBALS['hubleto'] = $this;
  }

  public static function getGlobalApp(): \Hubleto\Framework\Loader
  {
    return $GLOBALS['hubleto'];
  }

  public function init(): void
  {

    try {
      $this->db()->init();
      $this->sessionManager()->start(false);

      $this->config()->init();
      $this->router()->init();
      $this->authProvider()->init();
      $this->permissionsManager()->init();
      $this->renderer()->init();
      $this->appManager()->init();
      $this->eventManager()->init();
      $this->emailProvider()->init();

    } catch (\Exception $e) {
      echo "Hubleto init failed: [".get_class($e)."] ".$e->getMessage() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }
  }

}
