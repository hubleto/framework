<?php

namespace Hubleto\Framework;

class DependencyInjection
{

  /**
   * [Description for $dependencies]
   *
   * @var array<string, string>
   */
  private array $dependencies = [];
  
  public function __construct(public \Hubleto\Framework\Loader $main) {

    $this->setDependency('model.user', \Hubleto\Framework\Models\User::class);

    $dependencies = $this->main->config->getAsArray('dependencies');
    foreach ($dependencies as $service => $class) {
      $this->setDependency($service, $class);
    }
  }
  
  public function setDependency(string $service, string $class): void
  {
    $this->dependencies[$service] = $class;
  }

  public function create(string $service): mixed
  {
    $class = $this->dependencies[$service] ?? $service;
    return (new $class($this->main));
  }
}