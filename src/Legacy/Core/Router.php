<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace Hubleto\Legacy\Core;

class Router {
  const HTTP_GET = 'HTTP_GET';

  public \Hubleto\Legacy\Core\Loader $app;

  public $routing = [];

  protected array $routesHttpGet = [];
  protected array $routeVars = [];
  
  public function __construct(\Hubleto\Legacy\Core\Loader $app) {
    $this->app = $app;

    $this->httpGet([
      'about' => \Hubleto\Legacy\Controllers\About::class,
      '/^api\/form\/describe\/?$/' => \Hubleto\Legacy\Controllers\Api\Form\Describe::class,
      '/^api\/table\/describe\/?$/' => \Hubleto\Legacy\Controllers\Api\Table\Describe::class,
      '/^api\/record\/get\/?$/' => \Hubleto\Legacy\Controllers\Api\Record\Get::class,
      '/^api\/record\/get-list\/?$/' => \Hubleto\Legacy\Controllers\Api\Record\GetList::class,
      '/^api\/record\/lookup\/?$/' => \Hubleto\Legacy\Controllers\Api\Record\Lookup::class,
      '/^api\/record\/save\/?$/' => \Hubleto\Legacy\Controllers\Api\Record\Save::class,
      '/^api\/record\/delete\/?$/' => \Hubleto\Legacy\Controllers\Api\Record\Delete::class,
    ]);
  }

  // 2024-12-04 NEW PRINCIPLE.

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

  // public function findController(string $method, string $route): string
  // {
  //   $controller = '';

  //   $tmpRoute = $this->findRoute($method, $route);

  //   if (!empty($tmpRoute['redirect'])) {
  //     $url = $tmpRoute['redirect']['url'];
  //     foreach ($m as $k => $v) {
  //       $url = str_replace('$'.$k, $v, $url);
  //     }
  //     $this->redirectTo($url, $tmpRoute['redirect']['code'] ?? 302);
  //     exit;
  //   } else if (is_string($tmpRoute)) {
  //     $controller = $tmpRoute;
  //   }

  //   return $controller;
  // }

  public function setRouteVars(array $routeVars): void
  {
    $this->routeVars = $routeVars;
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
    if (isset($this->params[$paramName])) {
      if (strtolower($this->routeVars[$varIndex]) === 'false') return false;
      else return (bool) ($this->routeVars[$varIndex] ?? false);
    } else {
      return false;
    }
  }

  public function redirectTo(string $url, int $code = 302) {
    header("Location: " . $this->app->config->getAsString('rootUrl') . "/" . trim($url, "/"), true, $code);
    exit;
  }

  public function createSignInController(): \Hubleto\Legacy\Core\Controller
  {
    $controller = new \Hubleto\Legacy\Core\Controller($this->app);
    $controller->requiresUserAuthentication = FALSE;
    $controller->hideDefaultDesktop = TRUE;
    $controller->translationContext = 'ADIOS\\Core\\Loader::Controllers\\SignIn';

    $controller->setView('@app/Views/SignIn.twig', ['status' => $_GET['incorrectLogin'] ?? '' == "1"]);
    return $controller;
  }

  public function createNotFoundController(): \Hubleto\Legacy\Core\Controller
  {
    $controller = new \Hubleto\Legacy\Core\Controller($this->app);
    $controller->requiresUserAuthentication = FALSE;
    $controller->hideDefaultDesktop = TRUE;
    $controller->translationContext = 'ADIOS\\Core\\Loader::Controllers\\NotFound';
    $controller->setView('@app/Views/NotFound.twig');
    return $controller;
  }

  public function createResetPasswordController(): \Hubleto\Legacy\Core\Controller
  {
    return new \Hubleto\Legacy\Core\Controller($this->app);
  }

  public function createDesktopController(): \Hubleto\Legacy\Core\Controller
  {
    $controller = new \Hubleto\Legacy\Core\Controller($this->app);
    $controller->translationContext = 'ADIOS\\Core\\Loader::Controllers\\Desktop';
    return $controller;
  }

}
