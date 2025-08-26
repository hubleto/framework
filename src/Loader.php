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

  public string $controller = "";
  public string $permission = "";
  public string $uid = "";
  public string $route = "";

  public array $modelObjects = [];

  public Logger $logger;
  public Locale $locale;
  public Interfaces\TranslatorInterface $translator;

  public \Illuminate\Database\Capsule\Manager $eloquent;

  public \Twig\Loader\FilesystemLoader $twigLoader;
  public \Twig\Environment $twig;

  public ?array $uploadedFiles = null;

  public function __construct(array $config = [])
  {
    parent::__construct($this);

    $this->setAsGlobal();

    // $this->params = $this->extractParamsFromRequest();

    try {

      foreach ($this->getServiceProviders() as $service => $provider) {
        DependencyInjection::setServiceProvider($service, $provider);
      }

      $this->getConfig()->setConfig($config);

      $this->createRenderer();

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

  // public function setParam(string $pName, mixed $pValue): void
  // {
  //   $this->params[$pName] = $pValue;
  // }

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

  public function createLogger(): Logger
  {
    return DependencyInjection::create($this, Logger::class);
  }

  public function createLocale(): Locale
  {
    return DependencyInjection::create($this, Locale::class);
  }

  public function createTranslator(): Interfaces\TranslatorInterface
  {
    return DependencyInjection::create($this, Translator::class);
  }

  public function createRenderer(): void
  {
    $this->twigLoader = new \Twig\Loader\FilesystemLoader();
    $this->twig = new \Twig\Environment($this->twigLoader, array(
      'cache' => false,
      'debug' => true,
    ));

    $this->configureRenderer();
  }

  /**
   * Creates object for HTML rendering (Twig).
   *
   * @return void
   * 
   */
  public function configureRenderer(): void
  {

    try {
      $this->twigLoader->addPath($this->getEnv()->projectFolder . '/views', 'app');
    } catch (\Exception $e) { }
    try {
      $this->twigLoader->addPath(realpath(__DIR__ . '/../views'), 'framework');
    } catch (\Exception $e) { }

    $this->twig->addGlobal('config', $this->getConfig()->get());
    $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
    $this->twig->addExtension(new \Twig\Extension\DebugExtension());

    $this->twig->addFunction(new \Twig\TwigFunction(
      'htmlentities',
      function ($string) {
        return mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8');
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'str2url',
      function ($string) {
        return Helper::str2url($string ?? '');
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'hasPermission',
      function (string $permission, array $idUserRoles = []) {
        return $this->getPermissionsManager()->granted($permission, $idUserRoles);
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'hasRole',
      function (int|string $role) {
        return $this->getPermissionsManager()->hasRole($role);
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'setTranslationContext',
      function ($context) {
        $this->translationContext = $context;
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'translate',
      function ($string, $context = '') {
        if (empty($context)) $context = $this->translationContext;
        return $this->translate($string, [], $context);
      }
    ));

    $this->twig->addFunction(new \Twig\TwigFunction(
      'number',
      function (string $amount) {
        return number_format((float) $amount, 2, ",", " ");
      }
    ));

  }

  //////////////////////////////////////////////////////////////////////////////
  // MISCELANEOUS

  public function getControllerClassName(string $controller) : string {
    return '\\' . trim(str_replace('/', '\\', $controller), '\\');
  }

  public function controllerExists(string $controller) : bool {
    return class_exists($this->getControllerClassName($controller));
  }

  ////////////////////////////////////////////////
  // metody pre pracu s konfiguraciou

  public function renderCSSCache() {
    $css = "";

    $cssFiles = [
      dirname(__FILE__)."/../Assets/Css/fontawesome-5.13.0.css",
      dirname(__FILE__)."/../Assets/Css/bootstrap.min.css",
      dirname(__FILE__)."/../Assets/Css/sb-admin-2.css",
      dirname(__FILE__)."/../Assets/Css/components.css",
      dirname(__FILE__)."/../Assets/Css/colors.css",
    ];

    foreach ($cssFiles as $file) {
      $css .= @file_get_contents($file)."\n";
    }

    return $css;
  }

  public function renderJSCache() {
    $js = "";

    $jsFiles = [
      dirname(__FILE__)."/../Assets/Js/hubleto.js",
      dirname(__FILE__)."/../Assets/Js/ajax_functions.js",
      dirname(__FILE__)."/../Assets/Js/base64.js",
      dirname(__FILE__)."/../Assets/Js/cookie.js",
      dirname(__FILE__)."/../Assets/Js/jquery-3.5.1.js",
      dirname(__FILE__)."/../Assets/Js/md5.js",
      dirname(__FILE__)."/../Assets/Js/moment.min.js",
    ];


    foreach ($jsFiles as $file) {
      $js .= (string) @file_get_contents($file) . ";\n";
    }

    return $js;
  }


  /**
   * Adds namespace for Twig renderer
   *
   * @param string $folder
   * @param string $namespace
   * 
   * @return void
   * 
   */
  public function addTwigViewNamespace(string $folder, string $namespace)
  {
    if (isset($this->twigLoader) && is_dir($folder)) {
      $this->twigLoader->addPath($folder, $namespace);
    }
  }

  public function getLanguage(): string
  {
    $user = $this->getAuth()->getUserFromSession() ?? [];
    if (isset($user['language']) && strlen($user['language']) == 2) {
      return $user['language'];
    } else if (isset($_COOKIE['language']) && strlen($_COOKIE['language']) == 2) {
      return $_COOKIE['language'];
    } else {
      $language = $this->getConfig()->getAsString('language', 'en');
      if (strlen($language) !== 2) $language = 'en';
      return $language;
    }
  }



  public static function getDictionaryFilename(string $language): string
  {
    if (strlen($language) == 2) {
      $reflection = new \ReflectionClass(get_called_class());
      $srcFolder = pathinfo((string) $reflection->getFilename(), PATHINFO_DIRNAME);
      return $srcFolder . '/' . static::RELATIVE_DICTIONARY_PATH . '/' . $language . '.json';
    } else {
      return '';
    }
  }

  /**
  * @return array|array<string, array<string, string>>
  */
  public static function loadDictionary(string $language): array
  {
    $dict = [];
    $dictFilename = static::getDictionaryFilename($language);
    if (is_file($dictFilename)) $dict = (array) @json_decode((string) file_get_contents($dictFilename), true);
    return $dict;
  }

  /**
  * @return array|array<string, array<string, string>>
  */
  public static function addToDictionary(string $language, string $contextInner, string $string): void
  {
    // $dictFilename = static::getDictionaryFilename($language);
    // if (is_file($dictFilename)) {
    //   $dict = static::loadDictionary($language);
    //   $dict[$contextInner][$string] = '';
    //   file_put_contents($dictFilename, json_encode($dict, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    // }
  }

  public function load(string $service, bool $noSingleton = false): mixed
  {
    return DependencyInjection::create($this, str_replace('/', '\\', $service), $noSingleton);
  }

}
