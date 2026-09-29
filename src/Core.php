<?php

namespace Hubleto\Framework;

/**
 * Shortcut to access all services used in the Hubleto project.
 */
class Core implements Interfaces\CoreInterface
{

  const DEBUG_LEVEL_NONE = 0;
  const DEBUG_LEVEL_ERROR = 1;
  const DEBUG_LEVEL_WARNING = 2;
  const DEBUG_LEVEL_INFO = 3;
  const DEBUG_LEVEL_DEBUG = 4;

  protected int $debugLevel = 0;

  public string $translationContext = '';
  public string $translationContextInner = '';

  public function __construct()
  {
  }

  /**
   * [Description for setDebugLevel]
   *
   * @param int $level
   * 
   * @return void
   * 
   */
  public function setDebugLevel(int $level): void
  {
    $this->debugLevel = $level;
  }

  /**
   * [Description for getDebugLevel]
   *
   * @return int
   * 
   */
  public function getDebugLevel(): int
  {
    return $this->debugLevel;
  }

  /**
   * Shortcut for the dependency injection.
   *
   * @param string $service
   * 
   * @return mixed
   * 
   */
  public static function getServiceStatic(string $service): mixed
  {
    return DependencyInjection::create($service);
  }

  /**
   * [Description for getService]
   *
   * @param string $service
   * 
   * @return mixed
   * 
   */
  public function getService(string $service): mixed
  {
    return DependencyInjection::create($service);
  }

  /**
   * Shortcut for the loader service.
   *
   * @return Loader
   * 
   */
  public function loader(): Interfaces\CoreInterface
  {
    return \Hubleto\Framework\Loader::getGlobalApp();
  }

  /**
   * Shortcut for the env service.
   *
   * @return Interfaces\EnvInterface
   * 
   */
  public function env(): Interfaces\EnvInterface
  {
    return $this->getService(Services\Env::class);
  }

  /**
   * Shortcut for the authentication service.
   *
   * @return Interfaces\AuthProviderInterface
   * 
   */
  public function authProvider(): Interfaces\AuthProviderInterface
  {
    return $this->getService(Services\AuthProvider::class);
  }

  /**
   * Shortcut for the database service.
   *
   * @return Interfaces\DbInterface
   * 
   */
  public function db(): Interfaces\DbInterface
  {
    return $this->getService(Services\Db::class);
  }

  /**
   * Shortcut for the app manager service.
   *
   * @return Interfaces\AppManagerInterface
   * 
   */
  public function appManager(): Interfaces\AppManagerInterface
  {
    return $this->getService(Services\AppManager::class);
  }

  /**
   * Shortcut for the router service.
   *
   * @return Interfaces\Router
   * 
   */
  public function router(): Interfaces\RouterInterface
  {
    return $this->getService(Services\Router::class);
  }

  /**
   * Shortcut for the event manager service.
   *
   * @return Interfaces\EventManagerInterface
   * 
   */
  public function eventManager(): Interfaces\EventManagerInterface
  {
    return $this->getService(Services\EventManager::class);
  }

  /**
   * Shortcut for the session manager service.
   *
   * @return Interfaces\SessionManagerInterface
   * 
   */
  public function sessionManager(): Interfaces\SessionManagerInterface
  {
    return $this->getService(Services\SessionManager::class);
  }

  /**
   * Shortcut for the permissions manager service.
   *
   * @return Interfaces\PermissionsManagerInterface
   * 
   */
  public function permissionsManager(): Interfaces\PermissionsManagerInterface
  {
    return $this->getService(Services\PermissionsManager::class);
  }

  /**
   * Shortcut for the cron manager service.
   *
   * @return Interfaces\CronManagerInterface
   * 
   */
  public function cronManager(): Interfaces\CronManagerInterface
  {
    return $this->getService(Services\CronManager::class);
  }

  /**
   * Shortcut for the config service.
   *
   * @return Interfaces\ConfigManagerInterface
   * 
   */
  public function config(): Interfaces\ConfigManagerInterface
  {
    return $this->getService(Services\ConfigManager::class);
  }

  /**
   * Shortcut for the terminal service.
   *
   * @return Interfaces\TerminalInterface
   * 
   */
  public function terminal(): Interfaces\TerminalInterface
  {
    return $this->getService(Services\Terminal::class);
  }

  /**
   * Shortcut for the logger service.
   *
   * @return Interfaces\LoggerInterface
   * 
   */
  public function logger(): Interfaces\LoggerInterface
  {
    return $this->getService(Services\Logger::class);
  }

  /**
   * Shortcut for the locale service.
   *
   * @return Interfaces\LocaleInterface
   * 
   */
  public function locale(): Interfaces\LocaleInterface
  {
    return $this->getService(Services\Locale::class);
  }

  /**
   * Shortcut for the renderer service.
   *
   * @return Interfaces\RendererInterface
   * 
   */
  public function renderer(): Interfaces\RendererInterface
  {
    return $this->getService(Services\Renderer::class);
  }

  /**
   * Shortcut for the translator service.
   *
   * @return Interfaces\TranslatorInterface
   * 
   */
  public function translator(): Interfaces\TranslatorInterface
  {
    return $this->getService(Services\Translator::class);
  }

  /**
   * [Description for getModel]
   *
   * @param string $model
   * 
   * @return Interfaces\ModelInterface
   * 
   */
  public function getModel(string $model): Interfaces\ModelInterface
  {
    return $this->getService($model);
  }

  /**
   * [Description for getController]
   *
   * @param string $controller
   * 
   * @return Controller
   * 
   */
  public function getController(string $controller): Interfaces\ControllerInterface
  {
    return $this->getService($controller);
  }

  /**
   * Shorthand for translator's translate() function.
   *
   * @param  string $string String to be translated
   * @param  array $vars Variables to be replaced
   * @return string Translated string.
   */
  /**
  * @param array<string, string> $vars
  */
  public function translate(string $string, array $vars = [], string $contextInner = ''): string
  {
    return $this->translator()->translate($this, $string, $vars, $contextInner);
  }
}