<?php

namespace Hubleto\Framework\Controllers;

class SignIn extends \Hubleto\Framework\Controller
{
  public bool $requiresUserAuthentication = false;
  public bool $hideDefaultDesktop = true;
  public string $translationContext = 'Hubleto\\Erp\\Loader::Controllers\\SignIn';
  public string $view = '@framework/SignIn.twig';
}
