<?php

namespace Hubleto\Framework;

register_shutdown_function(function() {
  $error = error_get_last();
  if ($error !== null && $error['type'] == E_ERROR) {
    header('HTTP/1.1 400 Bad Request', true, 400);
  }
});

class Loader extends CoreClass
{

  const RELATIVE_DICTIONARY_PATH = '../lang';

  public \Illuminate\Database\Capsule\Manager $eloquent;

  public function __construct(array $config = [])
  {
    parent::__construct($this);

    $this->setAsGlobal();

    try {

      foreach ($this->getServiceProviders() as $service => $provider) {
        DependencyInjection::setServiceProvider($service, $provider);
      }

      $this->getConfig()->setConfig($config);

    } catch (\Exception $e) {
      echo "Hubleto boot failed: [".get_class($e)."] ".$e->getMessage() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }

  }

  public function getServiceProviders(): array
  {
    return $this->getConfig()->getAsArray('serviceProviders');
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
      $this->initDatabaseConnections();
      $this->getSessionManager()->start(true);

      $this->getConfig()->init();
      $this->getRouter()->init();
      $this->getAuth()->init();
      $this->getPermissionsManager()->init();
      $this->getRenderer()->init();
      $this->getAppManager()->init();
      $this->getHookManager()->init();

    } catch (\Exception $e) {
      echo "Hubleto init failed: [".get_class($e)."] ".$e->getMessage() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }
  }

  public function initDatabaseConnections()
  {
    $dbHost = $this->getConfig()->getAsString('db_host', '');
    $dbPort = $this->getConfig()->getAsInteger('db_port', 3306);
    $dbName = $this->getConfig()->getAsString('db_name', '');
    $dbUser = $this->getConfig()->getAsString('db_user', '');
    $dbPassword = $this->getConfig()->getAsString('db_password', '');

    if (!empty($dbHost) && !empty($dbPort) && !empty($dbUser)) {
      $this->eloquent = new \Illuminate\Database\Capsule\Manager;
      $this->eloquent->setAsGlobal();
      $this->eloquent->bootEloquent();
      $this->eloquent->addConnection([
        "driver"    => "mysql",
        "host"      => $dbHost,
        "port"      => $dbPort,
        "database"  => $dbName ?? '',
        "username"  => $dbUser,
        "password"  => $dbPassword,
        "charset"   => 'utf8mb4',
        "collation" => 'utf8mb4_unicode_ci',
      ], 'default');

      $this->getPdo()->connect();
    }
  }

}
