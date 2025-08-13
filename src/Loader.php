<?php

namespace Hubleto\Framework;

register_shutdown_function(function() {
  $error = error_get_last();
  if ($error !== null && $error['type'] == E_ERROR) {
    header('HTTP/1.1 400 Bad Request', true, 400);
  }
});

class Loader
{
  const HUBLETO_MODE_FULL = 1;
  const HUBLETO_MODE_LITE = 2;

  const RELATIVE_DICTIONARY_PATH = '../Lang';

  public string $projectFolder = '';
  public string $projectUrl = '';

  public string $secureFolder = '';

  public string $requestedUri = "";
  public string $controller = "";
  public string $permission = "";
  public string $uid = "";
  public string $route = "";

  public array $modelObjects = [];

  public Config $config;
  public DependencyInjection $di;
  public Session $session;
  public Logger $logger;
  public Locale $locale;
  public Router $router;
  public Permissions $permissions;
  public Test $test;
  public Interfaces\AuthInterface $auth;
  public Translator $translator;
  public PDO $pdo;
  public Interfaces\AppManagerInterface $apps;

  public \Illuminate\Database\Capsule\Manager $eloquent;

  public \Twig\Loader\FilesystemLoader $twigLoader;
  public \Twig\Environment $twig;

  public string $translationContext = '';

  /** @property array<string, string> */
  protected array $params = [];

  public ?array $uploadedFiles = null;

  public string $srcFolder = '';

  public int $mode = 0;

  public function __construct(array $config = [], int $mode = self::HUBLETO_MODE_FULL)
  {
    $this->setAsGlobal();

    $this->params = $this->extractParamsFromRequest();

    $this->mode = $mode;

    $reflection = new \ReflectionClass($this);
    $this->srcFolder = pathinfo((string) $reflection->getFilename(), PATHINFO_DIRNAME);

    try {

      // load config
      $this->config = $this->createConfigManager($config);

      $this->projectFolder = $this->config->getAsString('projectFolder');
      $this->projectUrl = $this->config->getAsString('projectUrl');

      $this->secureFolder = $this->config->getAsString('secureFolder');
      if (empty($this->secureFolder)) $this->secureFolder = $this->projectFolder . '/secure';

      if (php_sapi_name() !== 'cli') {
        if (!empty($_GET['route'])) {
          $this->requestedUri = $_GET['route'];
        } else if ($this->config->getAsString('rewriteBase') == "/") {
          $this->requestedUri = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "/");
        } else {
          $this->requestedUri = str_replace(
            $this->config->getAsString('rewriteBase'),
            "",
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
          );
        }

      }

      // initialize dependency injector
      $this->di = $this->createDependencyInjection();

      foreach ($this->getServiceProviders() as $service => $provider) {
        $this->di->setServiceProvider($service, $provider);
      }

      // create required services
      $this->session = $this->createSessionManager();
      $this->logger = $this->createLogger();
      $this->translator = $this->createTranslator();
      $this->router = $this->createRouter();
      $this->locale = $this->createLocale();
      $this->permissions = $this->createPermissionsManager();
      $this->auth = $this->createAuthProvider();
      $this->test = $this->createTestProvider();
      $this->pdo = $this->createDbProvider();

      $this->createRenderer();

    } catch (\Exception $e) {
      echo "Hubleto boot failed: [".get_class($e)."] ".$e->getMessage() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }

  }

  public function getServiceProviders(): array
  {
    return $this->config->getAsArray('serviceProviders');
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
      $this->router->init();
      $this->permissions->init();
      $this->auth->init();

      if ($this->mode == self::HUBLETO_MODE_FULL) {
        $this->initDatabaseConnections();

        $this->session->start(true);

        $this->config->loadFromDB();

      }

    } catch (\Exception $e) {
      echo "Hubleto init failed: [".get_class($e)."] ".$e->getMessage() . "\n";
      echo $e->getTraceAsString() . "\n";
      exit;
    }
  }

  public function isAjax(): bool
  {
    return isset($_REQUEST['__IS_AJAX__']) && $_REQUEST['__IS_AJAX__'] == "1";
  }

  public function isWindow(): bool
  {
    return isset($_REQUEST['__IS_WINDOW__']) && $_REQUEST['__IS_WINDOW__'] == "1";
  }

  public function initDatabaseConnections()
  {
    $dbHost = $this->config->getAsString('db_host', '');
    $dbPort = $this->config->getAsInteger('db_port', 3306);
    $dbName = $this->config->getAsString('db_name', '');
    $dbUser = $this->config->getAsString('db_user', '');
    $dbPassword = $this->config->getAsString('db_password', '');

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

      $this->pdo->connect();
    }
  }

  public function createDependencyInjection(): DependencyInjection
  {
    return new DependencyInjection($this);
  }

  public function createTestProvider(): Test
  {
    return $this->di->create(Test::class);
  }

  public function createDbProvider(): PDO
  {
    return $this->di->create(PDO::class);
  }

  public function createAuthProvider(): Interfaces\AuthInterface
  {
    return $this->di->create(Auth\DefaultProvider::class);
  }

  public function createSessionManager(): Session
  {
    return $this->di->create(Session::class);
  }

  public function createConfigManager(array $config): Config
  {
    return new Config($this, $config);
  }

  public function createPermissionsManager(): Permissions
  {
    return $this->di->create(Permissions::class);
  }

  public function createRouter(): Router
  {
    return $this->di->create(Router::class);
  }

  public function createLogger(): Logger
  {
    return $this->di->create(Logger::class);
  }

  public function createLocale(): Locale
  {
    return $this->di->create(Locale::class);
  }

  public function createTranslator(): Translator
  {
    return $this->di->create(Translator::class);
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
      $this->twigLoader->addPath($this->projectFolder . '/views', 'app');
    } catch (\Exception $e) { }
    try {
      $this->twigLoader->addPath(realpath(__DIR__ . '/../views'), 'framework');
    } catch (\Exception $e) { }

    $this->twig->addGlobal('config', $this->config->get());
    $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
    $this->twig->addExtension(new \Twig\Extension\DebugExtension());

    $this->twig->addFunction(new \Twig\TwigFunction(
      'str2url',
      function ($string) {
        return Helper::str2url($string ?? '');
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'hasPermission',
      function (string $permission, array $idUserRoles = []) {
        return $this->permissions->granted($permission, $idUserRoles);
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'hasRole',
      function (int|string $role) {
        return $this->permissions->hasRole($role);
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
  // MODELS

  public function getModelClassName($modelName): string
  {
    return str_replace("/", "\\", $modelName);
  }

  /**
   * Returns the object of the model referenced by $modelName.
   * The returned object is cached into modelObjects property.
   *
   * @param  string $modelName Reference of the model. E.g. 'Hubleto/Framework/Models/User'.
   * @throws Exception If $modelName is not available.
   * @return object Instantiated object of the model.
   */
  public function getModel(string $modelName): Model
  {
    $modelClassName = $this->getModelClassName($modelName);
    return $this->di->create($modelClassName);
  }

  //////////////////////////////////////////////////////////////////////////////
  // TRANSLATIONS

  public function translate(string $string, array $vars = [], string $context = "Hubleto\Framework\Loader::root", $toLanguage = ""): string
  {
    return $this->translator->translate($string, $vars, $context, $toLanguage);
  }

  //////////////////////////////////////////////////////////////////////////////
  // MISCELANEOUS

  public function extractParamsFromRequest(): array {
    $route = '';
    $params = [];

    if (php_sapi_name() === 'cli') {
      $params = @json_decode($_SERVER['argv'][2] ?? "", true);
      if (!is_array($params)) { // toto nastane v pripade, ked $_SERVER['argv'] nie je JSON string
        $params = $_SERVER['argv'];
      }
      $route = $_SERVER['argv'][1] ?? "";
    } else {
      $params = Helper::arrayMergeRecursively(
        array_merge($_GET, $_POST),
        json_decode(file_get_contents("php://input"), true) ?? []
      );
      unset($params['route']);
    }

    return $params;
  }

  public function extractRouteFromRequest(): string {
    $route = '';

    if (php_sapi_name() === 'cli') {
      $route = $_SERVER['argv'][1] ?? "";
    } else {
      $route = $_REQUEST['route'] ?? '';
    }

    return $route;
  }

  /**
   * Renders the requested content. It can be the (1) whole desktop with complete <html>
   * content; (2) the HTML of a controller requested dynamically using AJAX; or (3) a JSON
   * string requested dynamically using AJAX and further processed in Javascript.
   *
   * @param  mixed $params Parameters (a.k.a. arguments) of the requested controller.
   * @throws Exception When running in CLI and requested controller is blocked for the CLI.
   * @throws Exception When running in SAPI and requested controller is blocked for the SAPI.
   * @return string Rendered content.
   */
  public function render(string $route = '', array $params = []): string
  {

    try {

      // Find-out which route is used for rendering

      if (empty($route)) $route = $this->extractRouteFromRequest();
      if (count($params) == 0) $params = $this->extractParamsFromRequest();

      $this->route = $route;
      // $this->params = $params;
      $this->uploadedFiles = $_FILES;

      // Apply routing and find-out which controller, permision and rendering params will be used
      // First, try the new routing principle with httpGet
      $routeData = $this->router->parseRoute(Router::HTTP_GET, $this->route);

      $this->controller = $routeData['controller'];
      $this->permission = '';

      $routeVars = $routeData['vars'];
      $this->router->setRouteVars($routeVars);

      foreach ($routeVars as $varName => $varValue) {
        $this->params[$varName] = $varValue;
      }

      if ($this->isUrlParam('sign-out')) {
        $this->auth->signOut();
      }

      if ($this->isUrlParam('signed-out')) {
        $this->router->redirectTo('');
        exit;
      }

      // Check if controller exists and if it can be used
      if (empty($this->controller)) {
        $controllerClassName = Controllers\NotFoundController::class;
      } else if (!$this->controllerExists($this->controller)) {
        throw new Exceptions\ControllerNotFound($this->controller);
      } else {
        $controllerClassName = $this->getControllerClassName($this->controller);
      }

      // authenticate user, if any
      $this->auth->auth();
      $this->config->filterByUser();

      // Create the object for the controller
      $controllerObject = $this->di->create($controllerClassName);

      if (empty($this->permission) && !empty($controllerObject->permission)) {
        $this->permission = $controllerObject->permission;
      }

      // Check if controller can be executed in this SAPI
      if (php_sapi_name() === 'cli') {
        /** @disregard P1014 */
        if (!$controllerClassName::$cliSAPIEnabled) {
          throw new Exceptions\GeneralException("Controller is not enabled in CLI interface.");
        }
      } else {
        /** @disregard P1014 */
        if (!$controllerClassName::$webSAPIEnabled) {
          throw new Exceptions\GeneralException("Controller is not enabled in WEB interface.");
        }
      }

      if ($controllerObject->requiresUserAuthentication) {
        if (!$this->auth->isUserInSession()) {
          $controllerObject = $this->router->createSignInController();
          $this->permission = $controllerObject->permission;
        }
      }

      if ($controllerObject->requiresUserAuthentication) {
        $this->permissions->check($this->permission);
      }

      $controllerObject->preInit();
      $controllerObject->init();
      $controllerObject->postInit();

      // All OK, rendering content...

      $return = '';

      unset($this->params['__IS_AJAX__']);

      $this->onBeforeRender();

      // Either return JSON string ...
      if ($controllerObject->returnType == Controller::RETURN_TYPE_JSON) {
        try {
          $returnArray = $controllerObject->renderJson();
        } catch (\Throwable $e) {
          http_response_code(400);

          $returnArray = [
            'status' => 'error',
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
          ];
        }
        $return = json_encode($returnArray);
      } elseif ($controllerObject->returnType == Controller::RETURN_TYPE_STRING) {
        $return = $controllerObject->renderString();
      } elseif ($controllerObject->returnType == Controller::RETURN_TYPE_NONE) {
        $controllerObject->run();
        $return = '';
      } else {
        $controllerObject->prepareView();

        $view = $controllerObject->getView();

        $contentParams = [
          'app' => $this,
          'uid' => $this->uid,
          'user' => $this->auth->getUser(),
          'config' => $this->config->get(),
          'routeUrl' => $this->route,
          'routeParams' => $this->params,
          'route' => $this->route,
          'session' => $this->session->get(),
          'controller' => $controllerObject,
          'viewParams' => $controllerObject->getViewParams(),
          'windowParams' => $controllerObject->getViewParams()['windowParams'] ?? null,
        ];

        if ($view !== null) {
          $contentHtml = $controllerObject->renderer->render(
            $view,
            $contentParams
          );
        } else {
          $contentHtml = $controllerObject->render($contentParams);
        }

        // In some cases the result of the view will be used as-is ...
        if (php_sapi_name() == 'cli' || $this->urlParamAsBool('__IS_AJAX__') || $controllerObject->hideDefaultDesktop) {
          $html = $contentHtml;

        // ... But in most cases it will be "encapsulated" in the desktop.
        } else {
          $desktopControllerObject = $this->router->createDesktopController();
          $desktopControllerObject->prepareView();

          if (isset($desktopControllerObject->renderer) && !empty($desktopControllerObject->getView())) {
            $desktopParams = $contentParams;
            $desktopParams['viewParams'] = array_merge($desktopControllerObject->getViewParams(), $contentParams['viewParams']);
            $desktopParams['contentHtml'] = $contentHtml;

            $html = $desktopControllerObject->renderer->render(
              $desktopControllerObject->getView(),
              $desktopParams
            );
          } else {
            $html = $contentHtml;
          }

        }

        $return = $html;
      }

      $this->onAfterRender();

      return $return;

    } catch (Exceptions\ControllerNotFound $e) {
      header('HTTP/1.1 400 Bad Request', true, 400);
      return $this->renderFatal('Controller not found: ' . $e->getMessage(), false);
    } catch (Exceptions\NotEnoughPermissionsException $e) {
      $message = $e->getMessage();
      if ($this->auth->isUserInSession()) {
        $message .= " Hint: Sign out at {$this->projectUrl}?sign-out and sign in again or check your permissions.";
      }
      return $this->renderFatal($message, false);
      // header('HTTP/1.1 401 Unauthorized', true, 401);
    } catch (Exceptions\GeneralException $e) {
      header('HTTP/1.1 400 Bad Request', true, 400);
      return "Hubleto run failed: [".get_class($e)."] ".$e->getMessage();
    } catch (\ArgumentCountError $e) {
      echo $e->getMessage();
      header('HTTP/1.1 400 Bad Request', true, 400);
      exit;
      return '';
    } catch (\Exception $e) {
      $error = error_get_last();

      if ($error && $error['type'] == E_ERROR) {
        $return = $this->renderFatal(
          '<div style="margin-bottom:1em;">'
            . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']
          . '</div>'
          . '<pre style="font-size:0.75em;font-family:Courier New">'
            . $e->getTraceAsString()
          . '</pre>',
          true
        );
      } else {
        $return = $this->renderFatal($this->renderExceptionHtml($e));
      }

      return $return;

      if (php_sapi_name() !== 'cli') {
        header('HTTP/1.1 400 Bad Request', true, 400);
      }
    }
  }

  public function getControllerClassName(string $controller) : string {
    return '\\' . trim(str_replace('/', '\\', $controller), '\\');
  }

  public function controllerExists(string $controller) : bool {
    return class_exists($this->getControllerClassName($controller));
  }

  public function renderSuccess($return) {
    return json_encode([
      "result" => "success",
      "message" => $return,
    ]);
  }

  public function renderWarning($message, $isHtml = true) {
    if ($this->isAjax() && !$this->isWindow()) {
      return json_encode([
        "status" => "warning",
        "message" => $message,
      ]);
    } else {
      return "
        <div class='alert alert-warning' role='alert'>
          ".($isHtml ? $message : htmlspecialchars($message))."
        </div>
      ";
    }
  }

  public function renderFatal($message, $isHtml = true) {
    if ($this->isAjax() && !$this->isWindow()) {
      return json_encode([
        "status" => "error",
        "message" => $message,
      ]);
    } else {
      return "
        <div class='alert alert-danger' role='alert' style='z-index:99999999'>
          ".($isHtml ? $message : htmlspecialchars($message))."
        </div>
      ";
    }
  }

  public function renderHtmlFatal($message) {
    return $this->renderFatal($message, true);
  }

  public function renderExceptionHtml($exception, array $args = []): string
  {

    $traceLog = "";
    foreach ($exception->getTrace() as $item) {
      $traceLog .= "{$item['file']}:{$item['line']}\n";
    }

    $errorMessage = $exception->getMessage();
    $errorHash = md5(date("YmdHis").$errorMessage);

    $errorDebugInfoHtml =
      "Error hash: {$errorHash}<br/>"
      . "<br/>"
      . "<div style='color:#888888'>"
        . get_class($exception) . "<br/>"
        . "Stack trace:<br/>"
        . "<div class='trace-log'>{$traceLog}</div>"
      . "</div>"
    ;

    $this->logger->error("{$errorHash}\t{$errorMessage}");

    switch (get_class($exception)) {
      case 'Hubleto\Framework\Exceptions\DBException':
        $html = "
          <div class='hubleto exception emoji'>🥴</div>
          <div class='hubleto exception message'>
            Oops! Something went wrong with the database.
          </div>
          <div class='hubleto exception message'>
            {$errorMessage}
          </div>
          {$errorDebugInfoHtml}
        ";
      break;
      case 'Illuminate\Database\QueryException':
      case 'Hubleto\Framework\Exceptions\DBDuplicateEntryException':

        if (get_class($exception) == 'Illuminate\Database\QueryException') {
          $dbQuery = $exception->getSql();
          $dbError = $exception->errorInfo[2];
          $errorNo = $exception->errorInfo[1];
        } else {
          list($dbError, $dbQuery, $initiatingModelName, $errorNo) = json_decode($exception->getMessage(), true);
        }

        $invalidColumns = [];

        if (!empty($initiatingModelName)) {
          $initiatingModel = $this->getModel($initiatingModelName);
          $columns = $initiatingModel->columns;
          $indexes = $initiatingModel->indexes();

          preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $dbError, $m);
          $invalidIndex = $m[2];
          $invalidColumns = [];
          foreach ($indexes[$invalidIndex]['columns'] as $columnName) {
            $invalidColumns[] = $columns[$columnName]->getTitle();
          }
        } else {
          preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $dbError, $m);
          if (!empty($m[2])) $invalidColumns = [$m[2]];
        }

        switch ($errorNo) {
          case 1216:
          case 1451:
            $errorMessage = "You cannot delete record that is linked with another records. Delete the linked records first.";
          break;
          case 1062:
          case 1217:
          case 1452:
            $errorMessage = "You are trying to save a record that is already existing.";
          break;
          default:
            $errorMessage = $dbError;
          break;
        }

        $html = "
          <div class='hubleto exception message'>
            ".$this->translate($errorMessage)."<br/>
            <br/>
            <b>".join(", ", $invalidColumns)."</b>
          </div>
          <a class='btn btn-small btn-transparent' onclick='$(this).next(\"pre\").slideToggle();'>
            <span class='text'>" . $this->translate('Show error details') . "</span>
          </a>
          <pre style='font-size:9px;text-align:left;display:none;padding-top:1em'>{$errorDebugInfoHtml}</pre>
        ";
      break;
      default:
        $html = "
          <div class='hubleto exception message'>
            Oops! Something went wrong.
          </div>
          <div class='hubleto exception message'>
            ".$exception->getMessage()."
          </div>
          {$errorDebugInfoHtml}
        ";
      break;
    }

    return $html;//$this->renderHtmlWarning($html);
  }

  public function renderHtmlWarning($warning) {
    return $this->renderWarning($warning, true);
  }

  ////////////////////////////////////////////////
  // metody pre pracu s konfiguraciou

  public function onBeforeRender(): void
  {
    // to be overriden
  }

  public function onAfterRender(): void
  {
    // to be overriden
  }


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




  public function getUrlParams(): array
  {
    return $this->params;
  }

  public function isUrlParam(string $paramName): bool
  {
    return isset($this->params[$paramName]);
  }

  public function urlParamNotEmpty(string $paramName): bool
  {
    return $this->isUrlParam($paramName) && !empty($this->params[$paramName]);
  }

  public function setUrlParam(string $paramName, string $newValue): void
  {
    $this->params[$paramName] = $newValue;
  }

  public function removeUrlParam(string $paramName): void
  {
    if (isset($this->params[$paramName])) unset($this->params[$paramName]);
  }

  public function urlParamAsString(string $paramName, string $defaultValue = ''): string
  {
    if (isset($this->params[$paramName])) return (string) $this->params[$paramName];
    else return $defaultValue;
  }

  public function urlParamAsInteger(string $paramName, int $defaultValue = 0): int
  {
    if (isset($this->params[$paramName])) return (int) $this->params[$paramName];
    else return $defaultValue;
  }

  public function urlParamAsFloat(string $paramName, float $defaultValue = 0): float
  {
    if (isset($this->params[$paramName])) return (float) $this->params[$paramName];
    else return $defaultValue;
  }

  public function urlParamAsBool(string $paramName, bool $defaultValue = false): bool
  {
    if (isset($this->params[$paramName])) {
      if (strtolower($this->params[$paramName]) === 'false') return false;
      else return (bool) $this->params[$paramName];
    } else return $defaultValue;
  }

  /**
  * @return array<string, string>
  */
  public function urlParamAsArray(string $paramName, array $defaultValue = []): array
  {
    if (isset($this->params[$paramName])) return (array) $this->params[$paramName];
    else return $defaultValue;
  }

  public function uploadedFile(string $paramName, ?array $defaultValue = null): null|array
  {
    if (isset($this->uploadedFiles[$paramName])) return $this->uploadedFiles[$paramName];
    else return $defaultValue;
  }

  public function getLanguage(): string
  {
    $user = (isset($this->auth) ? $this->auth->getUserFromSession() : []);
    if (isset($user['language']) && strlen($user['language']) == 2) {
      return $user['language'];
    } else if (isset($_COOKIE['language']) && strlen($_COOKIE['language']) == 2) {
      return $_COOKIE['language'];
    } else {
      $language = $this->config->getAsString('language', 'en');
      if (strlen($language) !== 2) $language = 'en';
      return $language;
    }
  }



  public static function getDictionaryFilename(string $language): string
  {
    if (strlen($language) == 2) {
      $appClass = get_called_class();
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
    $dictFilename = static::getDictionaryFilename($language);
    if (is_file($dictFilename)) {
      $dict = static::loadDictionary($language);
      $dict[$contextInner][$string] = '';
      file_put_contents($dictFilename, json_encode($dict, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
  }

  public function load(string $service): mixed
  {
    return $this->di->create($service);
  }

}
