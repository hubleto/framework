<?php

namespace Hubleto\Framework;

class Router extends CoreClass implements Interfaces\RouterInterface {
  const HTTP_GET = 'HTTP_GET';

  public $routing = [];

  protected string $route = '';
  protected array $routesHttpGet = [];
  protected array $routeVars = [];
  
  public function __construct(public \Hubleto\Framework\Loader $main)
  {

    $this->httpGet([
      '/^api\/form\/describe\/?$/' => \Hubleto\Framework\Controllers\Api\Form\Describe::class,
      '/^api\/table\/describe\/?$/' => \Hubleto\Framework\Controllers\Api\Table\Describe::class,
      '/^api\/record\/get\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Get::class,
      '/^api\/record\/get-list\/?$/' => \Hubleto\Framework\Controllers\Api\Record\GetList::class,
      '/^api\/record\/lookup\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Lookup::class,
      '/^api\/record\/save\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Save::class,
      '/^api\/record\/save-junction\/?$/' => \Hubleto\Framework\Controllers\Api\Record\SaveJunction::class,
      '/^api\/record\/delete\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Delete::class,
    ]);
  }

  public function init(): void
  {
  }

  public function extractParamsFromRequest(): array
  {
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

  public function extractRouteFromRequest(): string
  {
    $route = '';

    if (php_sapi_name() === 'cli') {
      $route = $_SERVER['argv'][1] ?? "";
    } else {
      $route = $_REQUEST['route'] ?? '';
    }

    return $route;
  }

  public function isAjax(): bool
  {
    return isset($_REQUEST['__IS_AJAX__']) && $_REQUEST['__IS_AJAX__'] == "1";
  }

  // configure routes for HTTP GET
  public function httpGet(array $routes)
  {
    $this->routesHttpGet = array_merge($this->routesHttpGet, $routes);
  }

  public function getRoutes(string $method): array
  {
    return match ($method) {
      self::HTTP_GET => $this->routesHttpGet,
      default => [],
    };
  }

  public function getRoute(): string
  {
    return $this->route;
  }

  public function setRoute(string $route): void
  {
    $this->route = $route;
  }

  /** array<string, array<string, string>> */
  public function parseRoute(string $method, string $route): array
  {
    $routeData = [
      'controller' => '',
      'vars' => [],
    ];
    foreach ($this->getRoutes($method) as $routePattern => $controller) {
      $routeMatch = true;
      $routeVars = [];

      if (
        str_starts_with($routePattern, '/')
        && str_ends_with($routePattern, '/')
        && preg_match($routePattern.'i', $route, $m)
      ) {
        unset($m[0]);
        $routeMatch = true;
        $routeVars = $m;
      } else {
        $routeMatch = $routePattern == $route;
        $routeVars = [];
      }

      if ($routeMatch) {
        if (!empty($controller['redirect'])) {
          $url = $controller['redirect']['url'];
          foreach ($m as $k => $v) {
            $url = str_replace('$'.$k, $v, $url);
          }
          $this->redirectTo($url, $controller['redirect']['code'] ?? 302);
          exit;
        } else if (is_string($controller)) {
          $routeData = [
            'controller' => $controller,
            'vars' => $routeVars,
          ];
        } else {
          $routeData = $controller;
        }
      }
    }

    return $routeData;
  }

  public function setRouteVars(array $routeVars): void
  {
    $this->routeVars = array_merge($this->routeVars, $routeVars);
  }

  public function getRouteVars(): array
  {
    return $this->routeVars;
  }

  public function getRouteVar($index): string
  {
    return $this->routeVars[$index] ?? '';
  }

  public function routeVarAsString($varIndex): string
  {
    return (string) ($this->routeVars[$varIndex] ?? '');
  }

  public function routeVarAsInteger($varIndex): int
  {
    return (int) ($this->routeVars[$varIndex] ?? 0);
  }

  public function routeVarAsFloat($varIndex): float
  {
    return (float) ($this->routeVars[$varIndex] ?? 0);
  }

  public function routeVarAsBool($varIndex): bool
  {
    if (isset($this->routeVars[$varIndex])) {
      if (strtolower($this->routeVars[$varIndex]) === 'false') return false;
      else return (bool) ($this->routeVars[$varIndex] ?? false);
    } else {
      return false;
    }
  }

  public function getUploadedFile(string $paramName, ?array $defaultValue = null): null|array
  {
    if (isset($_FILES[$paramName])) return $_FILES[$paramName];
    else return $defaultValue;
  }

  public function redirectTo(string $url, int $code = 302): void
  {
    header("Location: " . $this->getEnv()->projectUrl . "/" . trim($url, "/"), true, $code);
    exit;
  }

  public function createSignInController(): \Hubleto\Framework\Controller
  {
    $controller = new \Hubleto\Framework\Controller($this->main);
    $controller->requiresUserAuthentication = FALSE;
    $controller->hideDefaultDesktop = TRUE;
    $controller->translationContext = 'HubletoMain\\Loader::Controllers\\SignIn';

    $controller->setView('@app/SignIn.twig', ['status' => $_GET['incorrectLogin'] ?? '' == "1"]);
    return $controller;
  }

  public function createNotFoundController(): \Hubleto\Framework\Controller
  {
    return $this->getService(\Hubleto\Framework\Controllers\NotFoundController::class);
    $controller = new \Hubleto\Framework\Controller($this->main);
    $controller->requiresUserAuthentication = FALSE;
    $controller->hideDefaultDesktop = TRUE;
    $controller->translationContext = 'HubletoMain\\Loader::Controllers\\NotFound';
    $controller->setView('@app/NotFound.twig');
    return $controller;
  }

  public function createResetPasswordController(): \Hubleto\Framework\Controller
  {
    return $this->getService(\Hubleto\Framework\Controller::class);
  }

  public function createDesktopController(): \Hubleto\Framework\Controller
  {
    return $this->getService(\Hubleto\Framework\Controllers\DesktopController::class);
  }





  public function getUrlParams(): array
  {
    return $this->routeVars;
  }

  public function isUrlParam(string $paramName): bool
  {
    return isset($this->routeVars[$paramName]);
  }

  public function urlParamNotEmpty(string $paramName): bool
  {
    return $this->isUrlParam($paramName) && !empty($this->routeVars[$paramName]);
  }

  public function setUrlParam(string $paramName, string $newValue): void
  {
    $this->routeVars[$paramName] = $newValue;
  }

  public function removeUrlParam(string $paramName): void
  {
    if (isset($this->routeVars[$paramName])) unset($this->routeVars[$paramName]);
  }

  public function urlParamAsString(string $paramName, string $defaultValue = ''): string
  {
    if (isset($this->routeVars[$paramName])) return (string) $this->routeVars[$paramName];
    else return $defaultValue;
  }

  public function urlParamAsInteger(string $paramName, int $defaultValue = 0): int
  {
    if (isset($this->routeVars[$paramName])) return (int) $this->routeVars[$paramName];
    else return $defaultValue;
  }

  public function urlParamAsFloat(string $paramName, float $defaultValue = 0): float
  {
    if (isset($this->routeVars[$paramName])) return (float) $this->routeVars[$paramName];
    else return $defaultValue;
  }

  public function urlParamAsBool(string $paramName, bool $defaultValue = false): bool
  {
    if (isset($this->routeVars[$paramName])) {
      if (strtolower($this->routeVars[$paramName]) === 'false') return false;
      else return (bool) $this->routeVars[$paramName];
    } else return $defaultValue;
  }

  /**
  * @return array<string, string>
  */
  public function urlParamAsArray(string $paramName, array $defaultValue = []): array
  {
    if (isset($this->routeVars[$paramName])) return (array) $this->routeVars[$paramName];
    else return $defaultValue;
  }


}
