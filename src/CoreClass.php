<?php

namespace Hubleto\Framework;

class CoreClass
{
  public Loader $main;

  public function __construct(Loader $main)
  {
    $this->main = $main;
  }

  /**
   * [Description for getAppManager]
   *
   * @return Interfaces\AppManagerInterface
   * 
   */
  public function getAppManager(): Interfaces\AppManagerInterface
  {
    return $this->main->apps;
  }

  /**
   * [Description for getRouter]
   *
   * @return Router
   * 
   */
  public function getRouter(): Router
  {
    return $this->main->router;
  }

  /**
   * [Description for getConfig]
   *
   * @return Config
   * 
   */
  public function getConfig(): Config
  {
    return $this->main->config;
  }

}