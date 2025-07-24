<?php

namespace Hubleto\Legacy\Controllers;

class NotFound extends \Hubleto\Legacy\Core\Controller {
  public bool $requiresUserAuthentication = false;
  public bool $hideDefaultDesktop = true;

  public function prepareView(): void
  {
    $this->setView('@app/Views/404.twig');
  }
}