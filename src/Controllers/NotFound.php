<?php

namespace Hubleto\Framework\Controllers;

class NotFound extends \Hubleto\Framework\Controller
{
  public bool $requiresAuthenticatedUser = false;
  public bool $hideDefaultDesktop = true;
  public string $translationContext = 'Hubleto\\Erp\\Loader::Controllers\\NotFound';
  public string $view = '@framework/NotFound.twig';
}
