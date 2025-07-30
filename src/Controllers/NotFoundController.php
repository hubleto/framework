<?php

namespace Hubleto\Framework\Controllers;

class NotFoundController extends \Hubleto\Framework\Controller
{
  public bool $requiresUserAuthentication = false;
  public bool $hideDefaultDesktop = true;
  public string $translationContext = 'Hubleto\\Core\\Loader::Controllers\\NotFound';
  public string $view = '@framework/NotFound.twig';
}
