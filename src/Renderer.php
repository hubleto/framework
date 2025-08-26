<?php

namespace Hubleto\Framework;

class Renderer extends CoreClass
{

  public \Twig\Loader\FilesystemLoader $twigLoader;
  public \Twig\Environment $twig;

  public function init(): void
  {
    $this->twigLoader = new \Twig\Loader\FilesystemLoader();
    $this->twig = new \Twig\Environment($this->twigLoader, array(
      'cache' => false,
      'debug' => true,
    ));

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

  /**
   * Adds namespace for Twig renderer
   *
   * @param string $folder
   * @param string $namespace
   * 
   * @return void
   * 
   */
  public function addNamespace(string $folder, string $namespace)
  {
    if (isset($this->twigLoader) && is_dir($folder)) {
      $this->twigLoader->addPath($folder, $namespace);
    }
  }

  public function renderView(string $view, array $vars = []): string
  {
    return $this->twig->render($view, $vars);
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

      $router = $this->getRouter();
      $permissionManager = $this->getPermissionsManager();

      // Find-out which route is used for rendering

      if (empty($route)) $route = $router->extractRouteFromRequest();
      if (count($params) == 0) $params = $router->extractParamsFromRequest();

      $router->setRoute($route);

      // Apply routing and find-out which controller, permision and rendering params will be used
      // First, try the new routing principle with httpGet
      $routeData = $router->parseRoute(Router::HTTP_GET, $router->getRoute());

      $controllerClassName = $routeData['controller'];

      $routeVars = $routeData['vars'];
      $router->setRouteVars($params);
      $router->setRouteVars($routeVars);

      if ($router->isUrlParam('sign-out')) {
        $this->getAuth()->signOut();
      }

      if ($router->isUrlParam('signed-out')) {
        $router->redirectTo('');
        exit;
      }

      // Check if controller exists and if it can be used
      if (empty($controllerClassName)) {
        $controllerClassName = Controllers\NotFoundController::class;
      };
      
      // Create the object for the controller
      $controllerObject = DependencyInjection::create($this->main, $controllerClassName);

      // authenticate user, if any
      $this->getAuth()->auth();
      $this->getConfig()->filterByUser();

      if (empty($this->permission) && !empty($controllerObject->permission)) {
        $permissionManager->setPermission($controllerObject->permission);
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
        if (!$this->getAuth()->isUserInSession()) {
          $controllerObject = $this->getRouter()->createSignInController();
          $permissionManager->setPermission($controllerObject->permission);
        }
      }

      if ($controllerObject->requiresUserAuthentication) {
        $this->getPermissionsManager()->checkPermission();
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
          'user' => $this->getAuth()->getUser(),
          'config' => $this->getConfig()->get(),
          'routeUrl' => $router->getRoute(),
          'routeParams' => $this->getRouter()->getRouteVars(),
          'route' => $router->getRoute(),
          'session' => $this->getSessionManager()->get(),
          'controller' => $controllerObject,
          'viewParams' => $controllerObject->getViewParams(),
          'windowParams' => $controllerObject->getViewParams()['windowParams'] ?? null,
        ];

        if ($view !== null) {
          $contentHtml = $this->renderView(
            $view,
            $contentParams
          );
        } else {
          $contentHtml = $controllerObject->render($contentParams);
        }

        // In some cases the result of the view will be used as-is ...
        if (php_sapi_name() == 'cli' || $this->getRouter()->urlParamAsBool('__IS_AJAX__') || $controllerObject->hideDefaultDesktop) {
          $html = $contentHtml;

        // ... But in most cases it will be "encapsulated" in the desktop.
        } else {
          $desktopControllerObject = $this->getRouter()->createDesktopController();
          $desktopControllerObject->prepareView();

          if (!empty($desktopControllerObject->getView())) {
            $desktopParams = $contentParams;
            $desktopParams['viewParams'] = array_merge($desktopControllerObject->getViewParams(), $contentParams['viewParams']);
            $desktopParams['contentHtml'] = $contentHtml;

            $html = $this->renderView(
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
      if ($this->getAuth()->isUserInSession()) {
        $message .= " Hint: Sign out at {$this->getEnv()->projectUrl}?sign-out and sign in again or check your permissions.";
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

  public function renderSuccess($return): string
  {
    return json_encode([
      "result" => "success",
      "message" => $return,
    ]);
  }

  public function renderWarning($message, $isHtml = true): string
  {
    if ($this->getRouter()->isAjax()) {
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

  public function renderFatal($message, $isHtml = true): string
  {
    if ($this->getRouter()->isAjax()) {
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

  public function renderHtmlFatal($message): string
  {
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

    $this->getLogger()->error("{$errorHash}\t{$errorMessage}");

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
        $dbQuery = $exception->getSql();
        $dbError = $exception->errorInfo[2];
        $errorNo = $exception->errorInfo[1];

        if (in_array($errorNo, [1216, 1451])) {
          $model = $args[0];
          $errorMessage =
            "{$model->shortName} cannot be deleted because other data is linked to it."
          ;
        } elseif (in_array($errorNo, [1062, 1217, 1452])) {
          $errorMessage = "You are trying to save a record that is already existing.";
        } else {
          $errorMessage = $dbError;
        }
        $html = $this->translate($errorMessage);
      break;
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
          $columns = $initiatingModel->getColumns();
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
            ".$this->getTranslator()->translate($errorMessage)."<br/>
            <br/>
            <b>".join(", ", $invalidColumns)."</b>
          </div>
          <a class='btn btn-small btn-transparent' onclick='$(this).next(\"pre\").slideToggle();'>
            <span class='text'>" . $this->getTranslator()->translate('Show error details') . "</span>
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

  public function renderHtmlWarning($warning): string
  {
    return $this->renderWarning($warning, true);
  }

  public function onBeforeRender(): void
  {
    // to be overriden
  }

  public function onAfterRender(): void
  {
    // to be overriden
  }

}