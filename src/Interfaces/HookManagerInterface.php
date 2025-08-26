<?php declare(strict_types=1);

namespace Hubleto\Framework\Interfaces;

interface HookManagerInterface
{
  public function init(): void;
  public function log(string $msg): void;
  public function addHook(string $hookClass): void;
  public function getHooks(): array;
  public function run(string $trigger, array $args): void;
}
