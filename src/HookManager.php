<?php

namespace Hubleto\Framework;

class HookManager
{
  public \HubletoMain\Loader $main;

  /** @var array<\Hubleto\Framework\Controller\HookController> */
  protected array $hooks = [];

  public function __construct(\HubletoMain\Loader $main)
  {
    $this->main = $main;
  }

  public function init(): void
  {
    $hooks = @\Hubleto\Legacy\Core\Helper::scanDirRecursively($this->main->config->getAsString('srcFolder') . '/hooks');
    foreach ($hooks as $hook) {
      if (!\str_ends_with($hook, '.php')) continue;
      $hookClass = '\\HubletoMain\\Hook\\' . str_replace('/', '\\', $hook);
      $hookClass = str_replace('.php', '', $hookClass);
      $this->addHook($hookClass);
    }

    $hooks = @\Hubleto\Legacy\Core\Helper::scanDirRecursively($this->main->config->getAsString('rootFolder') . '/src/hooks');
    foreach ($hooks as $hook) {
      if (!\str_ends_with($hook, '.php')) continue;
      $hookClass = '\\HubletoProject\\Hook\\' . str_replace('/', '\\', $hook);
      $hookClass = str_replace('.php', '', $hookClass);
      $this->addHook($hookClass);
    }
  }

  public function log(string $msg): void
  {
    $this->main->logger->info($msg);
  }

  public function addHook(string $hookClass): void
  {
    $this->hooks[$hookClass] = new $hookClass($this->main);
  }

  public function getHooks(): array
  {
    return $this->hooks;
  }

  public function run(string $trigger, array $args)
  {
    foreach ($this->hooks as $hookClass => $hook) {
      $hook->run($trigger, $args);
    }
  }

}
