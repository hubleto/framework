<?php

namespace Hubleto\Framework;

class CoreClass
{

  public string $translationContext = '';

  public function __construct()
  {
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
  public function getAuthProvider(): Interfaces\AuthInterface
  {
    return $this->getService(AuthProvider::class);
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
   * [Description for getEmailProvider]
   *
   * @return EmailProvider
   * 
   */
  public function getEmailProvider(): EmailProvider
  {
    return $this->getService(EmailProvider::class);
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

  public function getRenderer(): Renderer
  {
    return $this->getService(Renderer::class);
  }

  public function getTranslator(): Interfaces\TranslatorInterface
  {
    $translator = $this->getService(Translator::class);
    $translator->setContext($this->translationContext);
    return $translator;
  }

  public function getModel(string $modelName): Model
  {
    return $this->getService($modelName);
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
    return $this->getTranslator()->translate($string, $vars);
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

  //   if ($main->getConfig()->getAsBool('autoTranslate')) {
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