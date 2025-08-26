<?php

namespace Hubleto\Framework;

register_shutdown_function(function() {
  $error = error_get_last();
  if ($error !== null && $error['type'] == E_ERROR) {
    header('HTTP/1.1 400 Bad Request', true, 400);
  }
});

class Loader extends Core
{

  const RELATIVE_DICTIONARY_PATH = '../lang';

  public function __construct(array $config = [])
  {
    parent::__construct();

    $this->setAsGlobal();

    try {
      $this->getConfig()->setConfig($config);

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

  public static function getGlobalApp(): \HubletoMain\Loader
  {
    return $GLOBALS['hubleto'];
  }

  public function init(): void
  {

    try {
      $this->getPdo()->init();
      $this->getSessionManager()->start(true);

      $this->getConfig()->init();
      $this->getRouter()->init();
      $this->getAuthProvider()->init();
      $this->getPermissionsManager()->init();
      $this->getRenderer()->init();
      $this->getAppManager()->init();
      $this->getHookManager()->init();
      $this->getEmailProvider()->init();

    } catch (\Exception $e) {
      echo "Hubleto init failed: [".get_class($e)."] ".$e->getMessage() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }
  }

}
