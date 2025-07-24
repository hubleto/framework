<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Controllers\ControllerForgotPassword;
use Hubleto\Framework\Controllers\ControllerResetPassword;
use Hubleto\Framework\Controllers\ControllerSignIn;
use Hubleto\Framework\Controllers\ControllerNotFound;

class Router extends \Hubleto\Legacy\Core\Router
{

  public \HubletoMain\Loader $main;

  public function __construct(\HubletoMain\Loader $app)
  {
    parent::__construct($app);

    $this->main = $app;

    $this->httpGet([
      '/^api\/get-apps-info\/?$/' => Api\GetAppsInfo::class,
      '/^api\/log-javascript-error\/?$/' => Api\LogJavascriptError::class,
      '/^api\/dictionary\/?$/' => Api\Dictionary::class,
      '/^api\/get-chart-data\/?$/' =>  Api\GetTemplateChartData::class,
      '/^api\/get-table-columns-customize\/?$/' =>  Api\GetTableColumnsCustomize::class,
      '/^api\/save-table-columns-customize\/?$/' =>  Api\SaveTableColumnsCustomize::class,
      '/^api\/table-export-csv\/?$/' =>  Api\TableExportCsv::class,
      '/^api\/table-import-csv\/?$/' =>  Api\TableImportCsv::class,
      '/^reset-password$/' => ControllerResetPassword::class,
      '/^forgot-password$/' => ControllerForgotPassword::class,
    ]);
  }

  public function createSignInController(): \Hubleto\Legacy\Core\Controller
  {
    return new ControllerSignIn($this->main);
  }

  public function createNotFoundController(): \Hubleto\Legacy\Core\Controller
  {
    return new ControllerNotFound($this->main);
  }

  public function createResetPasswordController(): \Hubleto\Legacy\Core\Controller
  {
    return new ControllerResetPassword($this->main);
  }

  public function createDesktopController(): \Hubleto\Legacy\Core\Controller
  {
    return $this->main->di->create(\HubletoApp\Community\Desktop\Controllers\Desktop::class);
  }

  public function httpGet(array $routes): void
  {
    parent::httpGet($routes);
  }

}
