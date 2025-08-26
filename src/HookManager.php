<?php declare(strict_types=1);

namespace Hubleto\Framework;

class HookManager extends Core implements Interfaces\HookManagerInterface
{
  /** @var array<\HubletoMain\Hook> */
  protected array $enabledHooks = [];

  public function init(): void
  {
    $hooks = @Helper::scanDirRecursively($this->getEnv()->srcFolder . '/../hooks');
    foreach ($hooks as $hook) {
      if (!\str_ends_with($hook, '.php')) continue;
      $hookClass = '\\HubletoMain\\Hook\\' . str_replace('/', '\\', $hook);
      $hookClass = str_replace('.php', '', $hookClass);
      $this->addHook($hookClass);
    }

    foreach ($this->getAppManager()->getInstalledApps() as $appNamespace => $app) {
      $hooks = @Helper::scanDirRecursively($app->srcFolder . '/Hooks');
      // var_dump($appNamespace);var_dump($hooks);
      foreach ($hooks as $hook) {
        if (!\str_ends_with($hook, '.php')) continue;
        $hookClass = '\\' . $appNamespace . '\\Hooks\\' . str_replace('/', '\\', $hook);
        $hookClass = str_replace('.php', '', $hookClass);
        $this->addHook($hookClass);
      }
    }

    $hooks = @Helper::scanDirRecursively($this->getEnv()->projectFolder . '/src/hooks');
    foreach ($hooks as $hook) {
      if (!\str_ends_with($hook, '.php')) continue;
      $hookClass = '\\HubletoProject\\Hook\\' . str_replace('/', '\\', $hook);
      $hookClass = str_replace('.php', '', $hookClass);
      $this->addHook($hookClass);
    }
  }

  public function log(string $msg): void
  {
    $this->getLogger()->info($msg);
  }

  public function addHook(string $hookClass): void
  {
    if (is_subclass_of($hookClass, \HubletoMain\Hook::class)) {
      $this->enabledHooks[$hookClass] = $this->getService($hookClass);
    }
  }

  public function getHooks(): array
  {
    return $this->enabledHooks;
  }

  public function run(string $trigger, array $args): void
  {
    foreach ($this->enabledHooks as $hookClass => $hook) {
      $hook->run($trigger, $args);
    }
  }

}
