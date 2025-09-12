<?php

namespace Hubleto\Framework;

/**
 * Shortcut to access all services used in the Hubleto project.
 */
class Core
{

  public string $translationContext = '';

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
  public function env(): Env
  {
    return $this->getService(Env::class);
  }

  /**
   * Shortcut for the database service.
   *
   * @return Db
   * 
   */
  public function db(): Db
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
  public function router(): Router
  {
    return $this->getService(Router::class);
  }

  /**
   * Shortcut for the hook manager service.
   *
   * @return HookManager
   * 
   */
  public function hookManager(): HookManager
  {
    return $this->getService(HookManager::class);
  }

  /**
   * Shortcut for the session manager service.
   *
   * @return SessionManager
   * 
   */
  public function sessionManager(): SessionManager
  {
    return $this->getService(SessionManager::class);
  }

  /**
   * Shortcut for the cron manager service.
   *
   * @return CronManager
   * 
   */
  public function cronManager(): CronManager
  {
    return $this->getService(CronManager::class);
  }

  /**
   * Shortcut for the email provider service.
   *
   * @return EmailProvider
   * 
   */
  public function emailProvider(): EmailProvider
  {
    return $this->getService(EmailProvider::class);
  }

  /**
   * Shortcut for the config service.
   *
   * @return Config
   * 
   */
  public function config(): Config
  {
    return $this->getService(Config::class);
  }

  /**
   * Shortcut for the logger service.
   *
   * @return Logger
   * 
   */
  public function logger(): Logger
  {
    return $this->getService(Logger::class);
  }

  /**
   * Shortcut for the locale service.
   *
   * @return Locale
   * 
   */
  public function locale(): Locale
  {
    return $this->getService(Locale::class);
  }

  /**
   * Shortcut for the renderer service.
   *
   * @return Renderer
   * 
   */
  public function renderer(): Renderer
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
    $translator = $this->getService(Translator::class);
    $translator->setContext($this->translationContext);
    return $translator;
  }

  /**
   * [Description for getModel]
   *
   * @param string $model
   * 
   * @return Models\Model
   * 
   */
  public function getModel(string $model): Models\Model
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
  public function getController(string $controller): Controller
  {
    return $this->getService($controller);
  }




  /**
   * Shorthand for core translate() function. Uses own language dictionary.
   *
   * @param  string $string String to be translated
   * @param  string $context Context where the string is used
   * @param  string $toLanguage Output language
   * @return string Translated string.
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->translator()->translate($string, $vars);
  }

  // public static function getDictionaryFilename(string $language): string
  // {
  //   if (strlen($language) == 2) {
  //     $reflection = new \ReflectionClass(get_called_class());
  //     $srcFolder = pathinfo((string) $reflection->getFilename(), PATHINFO_DIRNAME);
  //     return $srcFolder . '/Lang/' . $language . '.json';
  //   } else {
  //     return '';
  //   }
  // }

  // /**
  // * @return array|array<string, array<string, string>>
  // */
  // public static function loadDictionary(string $language): array
  // {
  //   $dict = [];
  //   $dictFilename = static::getDictionaryFilename($language);
  //   if (is_file($dictFilename)) $dict = (array) @json_decode((string) file_get_contents($dictFilename), true);
  //   return $dict;
  // }

  // /**
  // * @return array|array<string, array<string, string>>
  // */
  // public static function addToDictionary(string $language, string $contextInner, string $string): void
  // {
  //   $dictFilename = static::getDictionaryFilename($language);

  //   $dict = static::loadDictionary($language);

  //   $main = \Hubleto\Framework\Loader::getGlobalApp();

  //   if (!empty($dict[$contextInner][$string])) return;

  //   if ($main->config()->getAsBool('autoTranslate')) {
  //     /** @disregard P1009 */
  //     $tr = new \Stichoza\GoogleTranslate\GoogleTranslate();
  //     $tr->setSource('en'); // Translate from
  //     $tr->setTarget($language); // Translate to
  //     $dict[$contextInner][$string] = $tr->translate($string);
  //   } else {
  //     $dict[$contextInner][$string] = '';
  //   }

  //   @file_put_contents($dictFilename, json_encode($dict, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  // }

}