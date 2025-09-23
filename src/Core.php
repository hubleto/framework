<?php

namespace Hubleto\Framework;

/**
 * Shortcut to access all services used in the Hubleto project.
 */
class Core implements Interfaces\CoreInterface
{

  public string $translationContext = '';
  public string $translationContextInner = '';

  public function __construct()
  {
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
   * Shortcut for the env service.
   *
   * @return Env
   * 
   */
  public function env(): Interfaces\EnvInterface
  {
    return $this->getService(Env::class);
  }

  /**
   * Shortcut for the authentication service.
   *
   * @return Interfaces\AuthInterface
   * 
   */
  public function authProvider(): Interfaces\AuthInterface
  {
    return $this->getService(AuthProvider::class);
  }

  /**
   * Shortcut for the database service.
   *
   * @return Db
   * 
   */
  public function db(): Interfaces\DbInterface
  {
    return $this->getService(Db::class);
  }

  /**
   * Shortcut for the app manager service.
   *
   * @return Interfaces\AppManagerInterface
   * 
   */
  public function appManager(): Interfaces\AppManagerInterface
  {
    return $this->getService(AppManager::class);
  }

  /**
   * Shortcut for the router service.
   *
   * @return Router
   * 
   */
  public function router(): Interfaces\RouterInterface
  {
    return $this->getService(Router::class);
  }

  /**
   * Shortcut for the hook manager service.
   *
   * @return HookManager
   * 
   */
  public function hookManager(): Interfaces\HookManagerInterface
  {
    return $this->getService(HookManager::class);
  }

  /**
   * Shortcut for the session manager service.
   *
   * @return SessionManager
   * 
   */
  public function sessionManager(): Interfaces\SessionManagerInterface
  {
    return $this->getService(SessionManager::class);
  }

  /**
   * Shortcut for the permissions manager service.
   *
   * @return PermissionsManager
   * 
   */
  public function permissionsManager(): Interfaces\PermissionsManagerInterface
  {
    return $this->getService(PermissionsManager::class);
  }

  /**
   * Shortcut for the cron manager service.
   *
   * @return CronManager
   * 
   */
  public function cronManager(): Interfaces\CronManagerInterface
  {
    return $this->getService(CronManager::class);
  }

  /**
   * Shortcut for the email provider service.
   *
   * @return EmailProvider
   * 
   */
  public function emailProvider(): Interfaces\EmailProviderInterface
  {
    return $this->getService(EmailProvider::class);
  }

  /**
   * Shortcut for the config service.
   *
   * @return Interfaces\ConfigManagerInterface
   * 
   */
  public function config(): Interfaces\ConfigManagerInterface
  {
    return $this->getService(ConfigManager::class);
  }

  /**
   * Shortcut for the terminal service.
   *
   * @return Interfaces\TerminalInterface
   * 
   */
  public function terminal(): Interfaces\TerminalInterface
  {
    return $this->getService(Terminal::class);
  }

  /**
   * Shortcut for the logger service.
   *
   * @return Interfaces\LoggerInterface
   * 
   */
  public function logger(): Interfaces\LoggerInterface
  {
    return $this->getService(Logger::class);
  }

  /**
   * Shortcut for the locale service.
   *
   * @return Interfaces\LocaleInterface
   * 
   */
  public function locale(): Interfaces\LocaleInterface
  {
    return $this->getService(Locale::class);
  }

  /**
   * Shortcut for the renderer service.
   *
   * @return Interfaces\RendererInterface
   * 
   */
  public function renderer(): Interfaces\RendererInterface
  {
    return $this->getService(Renderer::class);
  }

  /**
   * Shortcut for the translator service.
   *
   * @return Interfaces\TranslatorInterface
   * 
   */
  public function translator(): Interfaces\TranslatorInterface
  {
    return $this->getService(Translator::class);
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