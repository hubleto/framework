<?php

namespace Hubleto\Framework;

/**
 * Configuration management for the Hubleto project.
 */
class Config extends Core
{
  protected array $configData = [];

  public function setConfig(array $configData)
  {
    $this->configData = $configData;
    $this->set('requestUri', $_SERVER['REQUEST_URI'] ?? "");
  }

  public function empty(string $path): bool
  {
    if (!isset($this->configData[$path])) return false;
    else return empty($this->configData[$path]);
  }

  public function get(string $path = '', $default = null): mixed
  {
    if ($path === '') return $this->configData;
    else {
      $config = $this->configData;
      foreach (explode('/', $path) as $key => $value) {
        if (isset($config[$value])) {
          $config = $config[$value];
        } else {
          $config = null;
        }
      }
      return ($config === null ? $default : $config);
    }
  }

  public function getAsString(string $path, string $defaultValue = ''): string
  {
    return (string) $this->get($path, $defaultValue);
  }

  public function getAsInteger(string $path, int $defaultValue = 0): int
  {
    return (int) $this->get($path, $defaultValue);
  }

  public function getAsFloat(string $path, float $defaultValue = 0): float
  {
    return (float) $this->get($path, $defaultValue);
  }

  public function getAsBool(string $path, bool $defaultValue = false): bool
  {
    return (bool) $this->get($path, $defaultValue);
  }

  public function getAsArray(string $path, array $defaultValue = []): array
  {
    return (array) $this->get($path, $defaultValue);
  }





  public function set(string $path, mixed $value): void
  {
    $path_array = explode('/', $path);

    $cfg = &$this->configData;
    foreach ($path_array as $path_level => $path_slice) {
      if ($path_level == count($path_array) - 1) {
        $cfg[$path_slice] = $value;
      } else {
        if (empty($cfg[$path_slice])) {
          $cfg[$path_slice] = null;
        }
        $cfg = &$cfg[$path_slice];
      }
    }
  }

  public function save(string $path, string $value): void
  {
    try {
      if (!empty($path)) {
        $this->db()->execute("
          insert into `config` set `path` = :path, `value` = :value
          on duplicate key update `path` = :path, `value` = :value
        ", ['path' => $path, 'value' => $value]);
      }
    } catch (\Exception $e) {
    }
  }

  public function saveForUser(string $path, string $value): void
  {
    $this->save('user/' . $this->authProvider()->getUserId() . '/' . $path, $value);
  }

  public function delete($path): void
  {
    try {
      if (!empty($path)) {
        $this->db()->execute("delete from `config` where `path` like ?", [$path . '%']);
      }
    } catch (\Exception $e) {
      if ($e->getCode() == '42S02') { // Base table not found
        // do nothing
      } else {
        throw $e; // forward exception to be processed by Hubleto framework
      }
    }
  }

  public function init(): void
  {
    if (!$this->db()->isConnected) return;

    try {
      $cfgs = $this->db()->fetchAll("select * from `config`");

      foreach ($cfgs as $cfg) {
        $tmp = &$this->configData;
        foreach (explode("/", $cfg['path']) as $tmp_path) {
          if (!isset($tmp[$tmp_path])) {
            $tmp[$tmp_path] = [];
          }
          $tmp = &$tmp[$tmp_path];
        }
        $tmp = $cfg['value'];
      }
    } catch (\Throwable $e) {
      // do nothing
      // if ($e->getCode() == '42S02') { // Base table not found
      //   // do nothing
      // } else {
      //   throw $e; // forward exception to be processed further
      // }
    }
  }

  public function filterByUser(): void
  {
    $idUser = $this->authProvider()->getUserId();
    if (isset($this->configData['user'][$idUser]) && is_array($this->configData['user'][$idUser])) {
      $this->configData = array_merge_recursive($this->configData, $this->configData['user'][$idUser]);
      unset($this->configData['user']);
    }
  }

}