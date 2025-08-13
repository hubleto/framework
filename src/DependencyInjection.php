<?php

namespace Hubleto\Framework;

class DependencyInjection
{

  /**
   * [Description for $serviceProviders]
   *
   * @var array<string, string>
   */
  private array $serviceProviders = [];

  private array $services = [];
  
  public function __construct(public \Hubleto\Framework\Loader $main) {
    $this->setServiceProvider('model.user', \Hubleto\Framework\Models\User::class);
  }
  
  public function setServiceProvider(string $service, string $provider): void
  {
    $this->serviceProviders[$service] = $provider;
  }

  public function create(string $service): mixed
  {
    $class = $this->serviceProviders[$service] ?? $service;
    if (!isset($this->services[$service])) {
      $this->services[$service] = (new $class($this->main));
    }
    return $this->services[$service];
  }
}