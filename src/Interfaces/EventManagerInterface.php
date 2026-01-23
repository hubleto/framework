<?php declare(strict_types=1);

namespace Hubleto\Framework\Interfaces;

interface EventManagerInterface
{
  public function init(): void;
  public function log(string $msg): void;
  public function addEventListener(string $event, EventListenerInterface $listener): void;
  public function getEventListeners(): array;
  public function fire(string $event, array $args): void;
}
