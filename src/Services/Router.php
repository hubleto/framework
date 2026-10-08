<?php

namespace Hubleto\Framework\Services;

use Hubleto\Framework\Interfaces\RouterInterface;
use Hubleto\Framework\Helper;
use Hubleto\Framework\Core;

/**
 * Default router for Hubleto project.
 */
class Router extends Core implements RouterInterface {
  const HTTP_GET = 'HTTP_GET';

  public $routing = [];

  protected string $route = '';
  protected array $routesHttpGet = [];
  protected array $routeVars = [];
  
  public function __construct()
  {

    $this->get([
      '/^api\/form-describe-and-load\/?$/' => \Hubleto\Framework\Controllers\Api\Form\DescribeAndLoad::class,
      '/^api\/table-describe-and-load\/?$/' => \Hubleto\Framework\Controllers\Api\Table\DescribeAndLoad::class,
      '/^api\/form\/describe\/?$/' => \Hubleto\Framework\Controllers\Api\Form\Describe::class,
      '/^api\/table\/describe\/?$/' => \Hubleto\Framework\Controllers\Api\Table\Describe::class,
      '/^api\/record\/get\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Get::class,
      '/^api\/record\/load-table-data\/?$/' => \Hubleto\Framework\Controllers\Api\Record\LoadTableData::class,
      '/^api\/record\/load-tree-data\/?$/' => \Hubleto\Framework\Controllers\Api\Record\LoadTreeData::class,
      '/^api\/record\/lookup\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Lookup::class,
      '/^api\/record\/save\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Save::class,
      '/^api\/record\/save-junction\/?$/' => \Hubleto\Framework\Controllers\Api\Record\SaveJunction::class,
      '/^api\/record\/delete\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Delete::class,
    ]);
  }

  /**
   * [Description for init]
   *
   * @return void
   * 
   */
  public function init(): void
  {
    $this->setRouteVars($this->extractParamsFromRequest());
  }

  /**
   * [Description for extractParamsFromRequest]
   *
   * @return array
   * 
   */
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

  /**
   * [Description for extractRouteFromRequest]
   *
   * @return string
   * 
   */
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

  /**
   * [Description for isAjax]
   *
   * @return bool
   * 
   */
  public function isAjax(): bool
  {
    return isset($_REQUEST['__IS_AJAX__']) && $_REQUEST['__IS_AJAX__'] == "1";
  }

  /**
   * [Description for get]
   *
   * @param array $routes
   * 
   * @return void
   * 
   */
  public function get(array $routes)
  {
    $this->routesHttpGet = array_merge($this->routesHttpGet, $routes);
  }

  public function crud(string $urlSlug, string $controllerClass): void
  {
    $urlSlugSanitized = str_replace('/', '\/', $urlSlug);
    $this->get([
      '/^' . $urlSlugSanitized . '(\/(?<recordId>\d+))?\/?$/' => $controllerClass,
      '/^' . $urlSlugSanitized . '\/add\/?$/' => ['controller' => $controllerClass, 'vars' => ['recordId' => -1]],
    ]);
  }

  /**
   * [Description for getRoutes]
   *
   * @param string $method
   * 
   * @return array
   * 
   */
  public function getRoutes(string $method): array
  {
    return match ($method) {
      self::HTTP_GET => $this->routesHttpGet,
      default => [],
    };
  }

  /**
   * [Description for getRoute]
   *
   * @return string
   * 
   */
  public function getRoute(): string
  {
    return $this->route;
  }

  /**
   * [Description for setRoute]
   *
   * @param string $route
   * 
   * @return void
   * 
   */
  public function setRoute(string $route): void
  {
    $this->route = $route;
  }

  /**
   * [Description for parseRoute]
   *
   * @param string $method
   * @param string $route
   * 
   * @return array
   * 
   */
  public function parseRoute(string $method, string $route): array
  {
    $routeData = [
      'controller' => '',
      'vars' => [],
    ];

    $routes = $this->getRoutes($method);

    $this->logger()->debug("Parsing route '{$route}' for method '{$method}'.");
    $this->logger()->debug("Available routes: " . json_encode($routes));

    foreach ($routes as $routePattern => $controller) {
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

  /**
   * [Description for setRouteVars]
   *
   * @param array $routeVars
   * 
   * @return void
   * 
   */
  public function setRouteVars(array $routeVars): void
  {
    $this->routeVars = array_merge($this->routeVars, $routeVars);
  }

  /**
   * [Description for getRouteVars]
   *
   * @return array
   * 
   */
  public function getRouteVars(): array
  {
    return $this->routeVars;
  }

  /**
   * [Description for getRouteVar]
   *
   * @param string|int $varIndex
   * 
   * @return string
   * 
   */
  public function getRouteVar(string|int $varIndex): string
  {
    return $this->routeVars[$varIndex] ?? '';
  }

  /**
   * [Description for routeVarAsString]
   *
   * @param string|int $varIndex
   * 
   * @return string
   * 
   */
  public function routeVarAsString(string|int $varIndex): string
  {
    return (string) ($this->routeVars[$varIndex] ?? '');
  }

  /**
   * [Description for routeVarAsInteger]
   *
   * @param string|int $varIndex
   * 
   * @return int
   * 
   */
  public function routeVarAsInteger(string|int $varIndex): int
  {
    return (int) ($this->routeVars[$varIndex] ?? 0);
  }

  /**
   * [Description for routeVarAsFloat]
   *
   * @param string|int $varIndex
   * 
   * @return float
   * 
   */
  public function routeVarAsFloat(string|int $varIndex): float
  {
    return (float) ($this->routeVars[$varIndex] ?? 0);
  }

  /**
   * [Description for routeVarAsBool]
   *
   * @param string|int $varIndex
   * 
   * @return bool
   * 
   */
  public function routeVarAsBool(string|int $varIndex): bool
  {
    if (isset($this->routeVars[$varIndex])) {
      if (strtolower($this->routeVars[$varIndex]) === 'false') return false;
      else return (bool) ($this->routeVars[$varIndex] ?? false);
    } else {
      return false;
    }
  }

  /**
   * [Description for getUploadedFile]
   *
   * @param string $paramName
   * @param array|null $defaultValue
   * 
   * @return null|array
   * 
   */
  public function getUploadedFile(string $paramName, ?array $defaultValue = null): null|array
  {
    if (isset($_FILES[$paramName])) return $_FILES[$paramName];
    else return $defaultValue;
  }

  public function redirectTo(string $route, int $code = 302, bool $ignoreLoops = false): void
  {

    if (php_sapi_name() === 'cli') return;

    $currentRoute = $this->extractRouteFromRequest();

    if ($ignoreLoops || $currentRoute != $route) {
      header("Location: " . $this->env()->projectUrl . "/" . trim($route, "/"), true, $code);
      exit;
    }
  }

  /**
   * [Description for getUrlParams]
   *
   * @return array
   * 
   */
  public function getUrlParams(): array
  {
    return $this->routeVars;
  }

  /**
   * [Description for isUrlParam]
   *
   * @param string $paramName
   * 
   * @return bool
   * 
   */
  public function isUrlParam(string $paramName): bool
  {
    return isset($this->routeVars[$paramName]);
  }

  /**
   * [Description for urlParamNotEmpty]
   *
   * @param string $paramName
   * 
   * @return bool
   * 
   */
  public function urlParamNotEmpty(string $paramName): bool
  {
    return $this->isUrlParam($paramName) && !empty($this->routeVars[$paramName]);
  }

  /**
   * [Description for setUrlParam]
   *
   * @param string $paramName
   * @param string $newValue
   * 
   * @return void
   * 
   */
  public function setUrlParam(string $paramName, string $newValue): void
  {
    $this->routeVars[$paramName] = $newValue;
  }

  /**
   * [Description for removeUrlParam]
   *
   * @param string $paramName
   * 
   * @return void
   * 
   */
  public function removeUrlParam(string $paramName): void
  {
    if (isset($this->routeVars[$paramName])) unset($this->routeVars[$paramName]);
  }

  /**
   * [Description for urlParamAsString]
   *
   * @param string $paramName
   * @param string $defaultValue
   * 
   * @return string
   * 
   */
  public function urlParamAsString(string $paramName, string $defaultValue = ''): string
  {
    if (isset($this->routeVars[$paramName])) return (string) $this->routeVars[$paramName];
    else return $defaultValue;
  }

  /**
   * [Description for urlParamAsInteger]
   *
   * @param string $paramName
   * @param int $defaultValue
   * 
   * @return int
   * 
   */
  public function urlParamAsInteger(string $paramName, int $defaultValue = 0): int
  {
    if (isset($this->routeVars[$paramName])) return (int) $this->routeVars[$paramName];
    else return $defaultValue;
  }

  /**
   * [Description for urlParamAsFloat]
   *
   * @param string $paramName
   * @param float $defaultValue
   * 
   * @return float
   * 
   */
  public function urlParamAsFloat(string $paramName, float $defaultValue = 0): float
  {
    if (isset($this->routeVars[$paramName])) return (float) $this->routeVars[$paramName];
    else return $defaultValue;
  }

  /**
   * [Description for urlParamAsBool]
   *
   * @param string $paramName
   * @param bool $defaultValue
   * 
   * @return bool
   * 
   */
  public function urlParamAsBool(string $paramName, bool $defaultValue = false): bool
  {
    if (isset($this->routeVars[$paramName])) {
      if (strtolower($this->routeVars[$paramName]) === 'false') return false;
      else return (bool) $this->routeVars[$paramName];
    } else return $defaultValue;
  }

  /**
   * [Description for urlParamAsArray]
   *
   * @param string $paramName
   * @param array $defaultValue
   * 
   * @return array
   * 
   */
  public function urlParamAsArray(string $paramName, array $defaultValue = []): array
  {
    if (isset($this->routeVars[$paramName])) return (array) $this->routeVars[$paramName];
    else return $defaultValue;
  }


}
