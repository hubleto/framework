<?php

namespace Hubleto\Framework;

/**
 * Default implementation of dependency injection.
 */
class DependencyInjection
{

  /**
   * [Description for $serviceProviders]
   *
   * @var array<string, string>
   */
  private static array $serviceProviders = [];

  private static array $services = [];
  
  // public static function __construct(public \Hubleto\Framework\Loader $main) {
  //   self::setServiceProvider('model.user', \Hubleto\Framework\Models\User::class);
  // }
  
  public static function setServiceProvider(string $service, string $provider): void
  {
    self::$serviceProviders[$service] = $provider;
  }

  public static function setServiceProviders(array $providers): void
  {
    foreach ($providers as $service => $provider) {
      self::setServiceProvider($service, $provider);
    }
  }

  public static function create(
    // \Hubleto\Framework\Loader $main,
    string $service,
    bool $noSingleton = false
  ): mixed
  {
    $service = str_replace("/", "\\", $service);

    $class = self::$serviceProviders[$service] ?? $service;

    if ($noSingleton) {
      $serviceObj = new $class(/*$main*/);
    } else {
      if (!isset(self::$services[$service])) {
        self::$services[$service] = new $class(/*$main*/);
      }
      $serviceObj = self::$services[$service];
    }

    return $serviceObj;
  }
}