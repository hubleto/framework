<?php declare(strict_types=1);

namespace Hubleto\Framework;

class EventListener extends Core
{

  // An event listener shall implement the `onEventName` methods.
  // The event listeners are called with `__call()` magic methos.

  // For example, a method called 'onControllerSetView' will be
  // called when an event 'onControllerSetView' will be fired.

}