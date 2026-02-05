<?php

namespace Hubleto\Framework\Controllers;

class NotEnoughPermissions extends \Hubleto\Erp\Controller
{
  public bool $requiresAuthenticatedUser = false;
  public bool $hideDefaultDesktop = true;
  public string $translationContext = 'Hubleto\\Erp\\Loader::Controllers\\NotEnoughPermissions';
  public string $view = '@framework/NotEnoughPermissions.twig';

  public string $message = '';


  public function prepareView(): void
  {
    $this->viewParams['message'] = $this->message;
  }
}
