<?php declare(strict_types=1);

namespace Hubleto\Framework\Services;

use Hubleto\Framework\Interfaces\EventManagerInterface;
use Hubleto\Framework\Interfaces\EventListenerInterface;
use Hubleto\Framework\Core;

/**
 * Default manager for event listeners in the Hubleto project.
 */
class EventManager extends Core implements EventManagerInterface
{
  /** @var array<EventListenerInterface> */
  protected array $listeners = [];

  public function init(): void
  {
  }

  public function log(string $msg): void
  {
    $this->logger()->info($msg);
  }

  public function addEventListener(string $event, EventListenerInterface $listener): void
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
