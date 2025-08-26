<?php

namespace Hubleto\Framework;

class CoreClass
{
  public Loader $main;

  public function __construct(Loader $main)
  {
    $this->main = $main;
  }

  public function getService(string $service): mixed
  {
    return DependencyInjection::create($this->main, $service);
  }

  /**
   * [Description for getEnv]
   *
   * @return Env
   * 
   */
  public function getEnv(): Env
  {
    return $this->getService(Env::class);
  }

  /**
   * [Description for getAuth]
   *
   * @return Interfaces\AuthInterface
   * 
   */
  public function getAuth(): Interfaces\AuthInterface
  {
    return $this->getService(Auth\DefaultProvider::class);
  }

  /**
   * [Description for getPdo]
   *
   * @return PDO
   * 
   */
  public function getPdo(): PDO
  {
    return $this->getService(PDO::class);
  }

  /**
   * [Description for getAppManager]
   *
   * @return Interfaces\AppManagerInterface
   * 
   */
  public function getAppManager(): Interfaces\AppManagerInterface
  {
    return $this->getService(AppManager::class);
  }

  /**
   * [Description for getRouter]
   *
   * @return Router
   * 
   */
  public function getRouter(): Router
  {
    return $this->getService(Router::class);
  }

  /**
   * [Description for getHookManager]
   *
   * @return HookManager
   * 
   */
  public function getHookManager(): HookManager
  {
    return $this->getService(HookManager::class);
  }

  /**
   * [Description for getSessionManager]
   *
   * @return SessionManager
   * 
   */
  public function getSessionManager(): SessionManager
  {
    return $this->getService(SessionManager::class);
  }

  /**
   * [Description for getPermissionsManager]
   *
   * @return PermissionsManager
   * 
   */
  public function getPermissionsManager(): PermissionsManager
  {
    return $this->getService(PermissionsManager::class);
  }

  /**
   * [Description for getCronManager]
   *
   * @return CronManager
   * 
   */
  public function getCronManager(): CronManager
  {
    return $this->getService(CronManager::class);
  }

  /**
   * [Description for getConfig]
   *
   * @return Config
   * 
   */
  public function getConfig(): Config
  {
    return $this->getService(Config::class);
  }

  public function getLogger(): Logger
  {
    return $this->getService(Logger::class);
  }

  public function getLocale(): Locale
  {
    return $this->getService(Locale::class);
  }

  public function getTranslator(): Interfaces\TranslatorInterface
  {
    return $this->getService(Translator::class);
  }

  public function getModel(string $modelName): Model
  {
    return $this->getService($modelName);
  }


}