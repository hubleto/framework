<?php

namespace Hubleto\Framework\Interfaces;

interface RouterInterface {

  public function init(): void;
  public function isAjax(): bool;
  public function extractParamsFromRequest(): array;
  public function extractRouteFromRequest(): string;
  public function httpGet(array $routes);
  public function getRoutes(string $method): array;
  public function parseRoute(string $method, string $route): array;
  public function getRoute(): string;
  public function setRoute(string $route): void;
  public function setRouteVars(array $routeVars): void;
  public function getRouteVars(): array;
  public function getRouteVar($index): string;
  public function routeVarAsString($varIndex): string;
  public function routeVarAsInteger($varIndex): int;
  public function routeVarAsFloat($varIndex): float;
  public function routeVarAsBool($varIndex): bool;
  public function redirectTo(string $url, int $code = 302): void;
  public function getUrlParams(): array;
  public function isUrlParam(string $paramName): bool;
  public function urlParamNotEmpty(string $paramName): bool;
  public function setUrlParam(string $paramName, string $newValue): void;
  public function removeUrlParam(string $paramName): void;
  public function urlParamAsString(string $paramName, string $defaultValue = ''): string;
  public function urlParamAsInteger(string $paramName, int $defaultValue = 0): int;
  public function urlParamAsFloat(string $paramName, float $defaultValue = 0): float;
  public function urlParamAsBool(string $paramName, bool $defaultValue = false): bool;
  public function urlParamAsArray(string $paramName, array $defaultValue = []): array;

}
