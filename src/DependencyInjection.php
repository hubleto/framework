<?php

namespace Hubleto\Framework;

class DependencyInjection extends \Hubleto\Legacy\Core\DependencyInjection {

  use \Hubleto\Framework\Traits\MainTrait;

  public function __construct(\HubletoMain\Loader $main) {
    parent::__construct($main);
    $this->main = $main;

    $dependencies = $this->main->config->getAsArray('dependencies');
    foreach ($dependencies as $service => $class) {
      $this->setDependency($service, $class);
    }
  }
  
}