<?php

namespace Hubleto\Legacy\Controllers;

class About extends \Hubleto\Legacy\Core\Controller {
  public bool $requiresUserAuthentication = false;
  public bool $hideDefaultDesktop = true;

  public function render(array $params): string
  {
    return 'This is Adios application. Your appNamespace is ' . $this->app->config->getAsString('appNamespace') . '.';
  }
}