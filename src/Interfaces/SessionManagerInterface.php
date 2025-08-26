<?php

namespace Hubleto\Framework\Interfaces;

interface SessionManagerInterface
{

  public function getSalt(): string;
  public function start(bool $persist, array $options = []): void;
  public function stop(): void;
  public function set(string $path, mixed $value, string $key = '');
  public function get(string $path = '', string $key = ''): mixed;
  public function push(string $path, mixed $value): void;
  public function isset(string $path): bool;
  public function unset(string $path): void;
  public function clear(): void;

}