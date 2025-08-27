<?php

namespace Hubleto\Framework;

class Env extends Core
{

  public string $projectFolder = '';
  public string $projectUrl = '';

  public string $secureFolder = '';
  public string $uploadFolder = '';

  public string $srcFolder = '';
  public string $releaseFolder = '.';

  public string $assetsUrl = '';

  public string $requestedUri = "";

  public function __construct()
  {
    parent::__construct();

    $reflection = new \ReflectionClass($this);
    $this->srcFolder = pathinfo((string) $reflection->getFilename(), PATHINFO_DIRNAME);

    $this->projectFolder = $this->getConfig()->getAsString('projectFolder');
    $this->projectUrl = $this->getConfig()->getAsString('projectUrl');

    $this->secureFolder = $this->getConfig()->getAsString('secureFolder');
    if (empty($this->secureFolder)) $this->secureFolder = $this->projectFolder . '/secure';

    $this->uploadFolder = $this->getConfig()->getAsString('uploadFolder');
    if (empty($this->uploadFolder)) $this->secureFolder = $this->projectFolder . '/upload';

    $this->assetsUrl = $this->getConfig()->getAsString('assetsUrl');

    if (php_sapi_name() !== 'cli') {
      if (!empty($_GET['route'])) {
        $this->requestedUri = $_GET['route'];
      } else if ($this->getConfig()->getAsString('rewriteBase') == "/") {
        $this->requestedUri = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "/");
      } else {
        $this->requestedUri = str_replace(
          $this->getConfig()->getAsString('rewriteBase'),
          "",
          parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        );
      }

    }
  }


}