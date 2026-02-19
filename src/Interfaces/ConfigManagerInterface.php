<?php

namespace Hubleto\Framework\Interfaces;

interface ConfigManagerInterface extends CoreInterface
{

  public function forApp(string $appClass);
  public function forModel(string $modelClass);
  public function setPrefix(string $prefix);
  public function setConfig(array $configData);
  public function empty(string $path): bool;
  public function get(string $path = '', $default = null): mixed;
  public function getAsString(string $path, string $defaultValue = ''): string;
  public function getAsInteger(string $path, int $defaultValue = 0): int;
  public function getAsFloat(string $path, float $defaultValue = 0): float;
  public function getAsBool(string $path, bool $defaultValue = false): bool;
  public function getAsArray(string $path, array $defaultValue = []): array;
  public function set(string $path, mixed $value): void;
  public function save(string $path, string $value): void;
  public function saveForUser(string $path, string $value): void;
  public function delete($path): void;
  public function init(): void;
  public function filterByUser(): void;

}