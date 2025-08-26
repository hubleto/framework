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
    return DependencyInjection::create($this->main, Env::class);
  }

  /**
   * [Description for getAuth]
   *
   * @return Interfaces\AuthInterface
   * 
   */
  public function getAuth(): Interfaces\AuthInterface
  {
    return DependencyInjection::create($this->main, Auth\DefaultProvider::class);
  }

  /**
   * [Description for getPdo]
   *
   * @return PDO
   * 
   */
  public function getPdo(): PDO
  {
    return DependencyInjection::create($this->main, PDO::class);
  }

  /**
   * [Description for getAppManager]
   *
   * @return Interfaces\AppManagerInterface
   * 
   */
  public function getAppManager(): Interfaces\AppManagerInterface
  {
    return DependencyInjection::create($this->main, AppManager::class);
  }

  /**
   * [Description for getRouter]
   *
   * @return Router
   * 
   */
  public function getRouter(): Router
  {
    return DependencyInjection::create($this->main, Router::class);
  }

  /**
   * [Description for getHookManager]
   *
   * @return HookManager
   * 
   */
  public function getHookManager(): HookManager
  {
    return DependencyInjection::create($this->main, HookManager::class);
  }

  /**
   * [Description for getSessionManager]
   *
   * @return SessionManager
   * 
   */
  public function getSessionManager(): SessionManager
  {
    return DependencyInjection::create($this->main, SessionManager::class);
  }

  /**
   * [Description for getPermissionsManager]
   *
   * @return PermissionsManager
   * 
   */
  public function getPermissionsManager(): PermissionsManager
  {
    return DependencyInjection::create($this->main, PermissionsManager::class);
  }

  /**
   * [Description for getCronManager]
   *
   * @return CronManager
   * 
   */
  public function getCronManager(): CronManager
  {
    return DependencyInjection::create($this->main, CronManager::class);
  }

  /**
   * [Description for getConfig]
   *
   * @return Config
   * 
   */
  public function getConfig(): Config
  {
    return DependencyInjection::create($this->main, Config::class);
  }

  public function getLogger(): Logger
  {
    return DependencyInjection::create($this->main, Logger::class);
  }

  public function getLocale(): Locale
  {
    return DependencyInjection::create($this->main, Locale::class);
  }

  public function getTranslator(): Interfaces\TranslatorInterface
  {
    return DependencyInjection::create($this->main, Translator::class);
  }

  public function getModel(string $modelName): Model
  {
    return DependencyInjection::create($this->main, $modelName);
  }


}