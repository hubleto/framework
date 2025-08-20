<?php

namespace Hubleto\Framework;

class Router {
  const HTTP_GET = 'HTTP_GET';

  public $routing = [];

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
      '/^api\/record\/delete\/?$/' => \Hubleto\Framework\Controllers\Api\Record\Delete::class,
    ]);
  }

  public function init(): void
  {
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
    if (isset($this->params[$varIndex])) {
      if (strtolower($this->routeVars[$varIndex]) === 'false') return false;
      else return (bool) ($this->routeVars[$varIndex] ?? false);
    } else {
      return false;
    }
  }

  public function redirectTo(string $url, int $code = 302) {
    header("Location: " . $this->main->projectUrl . "/" . trim($url, "/"), true, $code);
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
    return $this->main->load(\Hubleto\Framework\Controllers\NotFoundController::class);
    $controller = new \Hubleto\Framework\Controller($this->main);
    $controller->requiresUserAuthentication = FALSE;
    $controller->hideDefaultDesktop = TRUE;
    $controller->translationContext = 'HubletoMain\\Loader::Controllers\\NotFound';
    $controller->setView('@app/NotFound.twig');
    return $controller;
  }

  public function createResetPasswordController(): \Hubleto\Framework\Controller
  {
    return $this->main->load(\Hubleto\Framework\Controller::class);
  }

  public function createDesktopController(): \Hubleto\Framework\Controller
  {
    return $this->main->load(\Hubleto\Framework\Controllers\DesktopController::class);
  }

}
