<?php

namespace Hubleto\Framework\Controllers;

class NotFound extends \Hubleto\Framework\Controller
{
  public bool $requiresUserAuthentication = false;
  public bool $hideDefaultDesktop = true;
  public string $translationContext = 'HubletoMain\\Loader::Controllers\\NotFound';
  public string $view = '@framework/NotFound.twig';
}
