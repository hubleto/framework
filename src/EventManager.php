<?php declare(strict_types=1);

namespace Hubleto\Framework;

/**
 * Default manager for event listeners in the Hubleto project.
 */
class EventManager extends Core implements Interfaces\EventManagerInterface
{
  /** @var array<\Hubleto\Framework\Event> */
  protected array $listeners = [];

  public function init(): void
  {
    // foreach ($this->appManager()->getInstalledApps() as $appNamespace => $app) {
    //   $hooks = @Helper::scanDirRecursively($app->srcFolder . '/Hooks');
    //   // var_dump($appNamespace);var_dump($hooks);
    //   foreach ($hooks as $hook) {
    //     if (!\str_ends_with($hook, '.php')) continue;
    //     $hookClass = '\\' . $appNamespace . '\\Hooks\\' . str_replace('/', '\\', $hook);
    //     $hookClass = str_replace('.php', '', $hookClass);
    //     $this->addHook($hookClass);
    //   }
    // }

    // $hooks = @Helper::scanDirRecursively($this->env()->projectFolder . '/src/hooks');
    // foreach ($hooks as $hook) {
    //   if (!\str_ends_with($hook, '.php')) continue;
    //   $hookClass = '\\HubletoProject\\Hook\\' . str_replace('/', '\\', $hook);
    //   $hookClass = str_replace('.php', '', $hookClass);
    //   $this->addHook($hookClass);
    // }
  }

  public function log(string $msg): void
  {
    $this->logger()->info($msg);
  }

  public function addEventListener(string $event, Interfaces\EventListenerInterface $listener): void
  {
    if (!isset($this->listeners[$event])) $this->listeners[$event] = [];
    $this->listeners[$event][] = $listener;
  }

  public function getEventListeners(): array
  {
    return $this->listeners;
  }

  public function fire(string $event, array $args): void
  {
    if (isset($this->listeners[$event]) && is_array($this->listeners[$event])) {
      foreach ($this->listeners[$event] as $listener) {
        call_user_func_array([$listener, $event], $args);
      }
    }
  }

}
