<?php

namespace Hubleto\Framework\Services;

use Hubleto\Framework\Core;
use Hubleto\Framework\Helper;
use Hubleto\Framework\Controller;
use Hubleto\Framework\Interfaces\RendererInterface;
use Hubleto\Framework\Exceptions\Exception;
use Hubleto\Framework\Exceptions\GeneralException;
use Hubleto\Framework\Exceptions\ControllerNotFound;
use Hubleto\Framework\Exceptions\NotEnoughPermissionsException;
use Hubleto\Framework\Controllers\NotFound;
use Hubleto\Framework\Controllers\SignIn;
use Hubleto\Framework\Controllers\Desktop;

/**
 * Default view renderer for Hubleto project.
 */
class Renderer extends Core implements RendererInterface
{

  public \Twig\Loader\FilesystemLoader $twigLoader;
  public \Twig\Environment $twig;

  public array $lastRenderedContentParams;
  public string $lastRenderedView;

  public function init(): void
  {
    $this->twigLoader = new \Twig\Loader\FilesystemLoader();
    $this->twig = new \Twig\Environment($this->twigLoader, array(
      'cache' => false,
      'debug' => true,
    ));

    $this->twigLoader->addPath(realpath(__DIR__ . '/../../views'), 'framework');

    try {
      $this->twigLoader->addPath($this->env()->projectFolder . '/views', 'app');
    } catch (\Exception $e) { }

    $this->twig->addGlobal('config', $this->config()->get());
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
        return $this->permissionsManager()->granted($permission, $idUserRoles);
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'hasRole',
      function (int|string $role) {
        return $this->permissionsManager()->hasRole($role);
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'setTranslationContext',
      function ($context, $contextInner) {
        $this->translationContext = $context;
        $this->translationContextInner = $contextInner;
      }
    ));
    $this->twig->addFunction(new \Twig\TwigFunction(
      'translate',
      function ($string, $vars = [], $context = '', $contextInner = '') {
        if (empty($context)) $context = $this->translationContext;
        if (empty($contextInner)) $contextInner = $this->translationContextInner;
        return $this->translate($string ?? '', $vars ?? [], $context . ':' . $contextInner);
      }
    ));

    $this->twig->addFunction(new \Twig\TwigFunction(
      'number',
      function (mixed $amount) {
        return number_format((float) $amount, 2, ",", " ");
      }
    ));

  }

  public function getTwig(): \Twig\Environment
  {
    return $this->twig;
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

    $this->lastRenderedView = '';
    $this->lastRenderedContentParams = [];

    try {

      $router = $this->router();

      /** @var PermissionManager */
      $permissionManager = $this->permissionsManager();

      $this->logger()->info("PermissionManager initiated.");

      /** @var AuthProvider */
      $authProvider = $this->getService(AuthProvider::class);

      $this->logger()->info("AuthManager initiated.");

      // Find-out which route is used for rendering

      if (empty($route)) $route = $router->extractRouteFromRequest();

      $this->logger()->info("Extracted route: " . $route);

      $router->setRoute($route);

      // Apply routing and find-out which controller, permision and rendering params will be used
      // First, try the new routing principle with httpGet
      $routeData = $router->parseRoute(Router::HTTP_GET, $router->getRoute());

      $this->logger()->info("Route parsed, controller: " . ($routeData['controller'] ?? 'n/a'));

      $controllerClassName = $routeData['controller'];

      $routeVars = $routeData['vars'];
      $router->setRouteVars($routeVars);
      $router->setRouteVars($params);

      $this->logger()->info("Route vars extracted: " . json_encode($routeVars));

      if ($router->isUrlParam('sign-out')) {
        $this->logger()->info("Signing out user as per request.");
        $authProvider->signOut();
      }

      if ($router->isUrlParam('signed-out')) {
        $this->logger()->info("Redirecting to home page after sign out.");
        $router->redirectTo('');
        exit;
      }

      // Check if controller exists and if it can be used
      if (empty($controllerClassName)) {
        $controllerClassName = NotFound::class;
      };
      
      // Create the object for the controller
      $controllerObject = $this->getController($controllerClassName);

      // authenticate user, if any
      $authProvider->auth();

      $this->logger()->info("User authenticated.");

      $this->config()->filterByUser();

      if (empty($this->permission) && !empty($controllerObject->permission)) {
        $permissionManager->setPermission($controllerObject->permission);
      }

      // Check if controller can be executed in this SAPI
      if (php_sapi_name() === 'cli') {
        /** @disregard P1014 */
        if (!$controllerClassName::$cliSAPIEnabled) {
          throw new GeneralException("Controller is not enabled in CLI interface.");
        }
      } else {
        /** @disregard P1014 */
        if (!$controllerClassName::$webSAPIEnabled) {
          throw new GeneralException("Controller is not enabled in WEB interface.");
        }
      }

      if ($controllerObject->requiresAuthenticatedUser) {
        if (!$authProvider->isUserInSession()) {
          $this->logger()->info("User not authenticated, redirecting to sign-in controller.");
          $controllerObject = $this->getController(SignIn::class);
          $permissionManager->setPermission($controllerObject->permission);
        }
      }

      if (
        $controllerObject->requiresAuthenticatedUser
        && !$controllerObject->permittedForAllUsers
      ) {
        $this->permissionsManager()->checkPermission();
      }

      $controllerObject->preInit();
      $controllerObject->init();
      $controllerObject->postInit();

      // All OK, rendering content...

      $return = '';

      $this->onBeforeRender();

      // Either return JSON string ...
      if ($controllerObject->returnType == Controller::RETURN_TYPE_JSON) {
        try {
          $returnArray = $controllerObject->renderJson();
        } catch (Exception $e) {
          http_response_code(400);

          $returnArray = $e->getResponseArray();
        }
        $return = json_encode($returnArray);
      } elseif ($controllerObject->returnType == Controller::RETURN_TYPE_STRING) {
        $return = $controllerObject->renderString();
      } elseif ($controllerObject->returnType == Controller::RETURN_TYPE_NONE) {
        $controllerObject->run();
        $return = '';
      } else {
        $controllerObject->prepareView();

        $this->lastRenderedView = $controllerObject->getView();
        
        $this->lastRenderedContentParams = [
          'hubleto' => $this,
          'user' => $authProvider->getUser(),
          'config' => $this->config()->get(),
          // 'routeUrl' => $router->getRoute(),
          // 'routeParams' => $this->router()->getRouteVars(),
          'route' => $router->getRoute(),
          // 'session' => $this->sessionManager()->get(),
          // 'controller' => $controllerObject,
          'viewParams' => $controllerObject->getViewParams(),
        ];

        if (empty($this->lastRenderedView)) {
          $contentHtml = $controllerObject->render();
        } else {
          $contentHtml = $this->renderView($this->lastRenderedView, $this->lastRenderedContentParams);
        }

        // In some cases the result of the view will be used as-is ...
        if (php_sapi_name() == 'cli' || $this->router()->urlParamAsBool('__IS_AJAX__') || $controllerObject->hideDefaultDesktop) {
          $html = $contentHtml;

        // ... But in most cases it will be "encapsulated" in the desktop.
        } else {
          $desktopControllerObject = $this->getController(Desktop::class);
          $desktopControllerObject->prepareView();

          if (!empty($desktopControllerObject->getView())) {
            $desktopParams = $this->lastRenderedContentParams;
            $desktopParams['viewParams'] = array_merge($desktopControllerObject->getViewParams(), $this->lastRenderedContentParams['viewParams']);
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

    } catch (ControllerNotFound $e) {
      return $this->renderFatal($e, false);
    } catch (NotEnoughPermissionsException $e) {
      header('HTTP/1.1 401 Unauthorized', true, 401);
      return $this->renderFatal($e, false);
    } catch (GeneralException $e) {
      header('HTTP/1.1 400 Bad Request', true, 400);
      return "Hubleto run failed: [".get_class($e)."] ".$e->getMessage();
    } catch (\ArgumentCountError $e) {
      echo $e->getMessage();
      header('HTTP/1.1 400 Bad Request', true, 400);
      exit;
      return '';
    } catch (Exception $e) {
      $error = error_get_last();

      if ($error && $error['type'] == E_ERROR) {
        $return = $this->renderFatal(
          $e
        );
      } else {
        $return = $this->renderFatal($e);
      }

      if (php_sapi_name() !== 'cli') {
        header('HTTP/1.1 400 Bad Request', true, 400);
      }

      return $return;
    } catch (\Exception $e) {
      header('HTTP/1.1 500 Internal Server Error', true, 500);
      return "Unhandled exception: [".get_class($e)."] ".$e->getMessage();
    }
  }

  public function renderSuccess($return): string
  {
    return json_encode([
      "result" => "success",
      "message" => $return,
    ]);
  }

  public function renderWarning(Exception $exception, $isHtml = true): string
  {
    if ($this->router()->isAjax()) {
      return json_encode($exception->getResponseArray());
    } else {
      return "
        <div class='alert alert-warning' role='alert'>
          ".($isHtml ? $exception->getMessage() : htmlspecialchars($exception->getMessage()))."
        </div>
      ";
    }
  }

  public function renderFatal(Exception $exception, $isHtml = true): string
  {
    header('HTTP/1.1 400 Bad Request', true, 400);

    if ($this->router()->isAjax()) {
      return json_encode($exception->getResponseArray());
    } else {
      return "
        <div class='alert alert-danger' role='alert' style='z-index:99999999'>
          ".($isHtml ? $exception->getMessage() : htmlspecialchars($exception->getMessage()))."
        </div>
      ";
    }
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