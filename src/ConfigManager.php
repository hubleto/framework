<?php

namespace Hubleto\Framework;

/**
 * Configuration management for the Hubleto project.
 */
class ConfigManager extends Core implements Interfaces\ConfigManagerInterface
{
  protected array $configData = [];
  protected array $configDataFull = [];
  private string $prefix = '';

  /**
   * [Description for forModel]
   *
   * @param string $modelClass
   * 
   * @return ConfigManager
   * 
   */
  public function forModel(string $modelClass): ConfigManager
  {
    /** @var Interfaces\ConfigManagerInterface */
    $new = new (get_class($this));
    $new->setConfig($this->configData);
    $new->setPrefix('models/' . $modelClass . '/');
    return $new;
  }

  /**
   * [Description for forApp]
   *
   * @param string $appClass
   * 
   * @return ConfigManager
   * 
   */
  public function forApp(string $appClass): ConfigManager
  {
    /** @var Interfaces\ConfigManagerInterface */
    $new = new (get_class($this));
    $new->setConfig($this->configData);
    $new->setPrefix('apps/' . str_replace('\\Loader', '', $appClass) . '/');
    return $new;
  }

  /**
   * [Description for setPrefix]
   *
   * @param string $prefix
   * 
   * @return void
   * 
   */
  public function setPrefix(string $prefix): void
  {
    $this->prefix = $prefix;
  }

  /**
   * [Description for getPrefix]
   *
   * @return string
   * 
   */
  public function getPrefix(): string
  {
    return $this->prefix;
  }

  /**
   * [Description for setConfig]
   *
   * @param array $configData
   * 
   * @return [type]
   * 
   */
  public function setConfig(array $configData)
  {
    $this->configData = $configData;
    $this->set('requestUri', $_SERVER['REQUEST_URI'] ?? "");
  }

  /**
   * [Description for empty]
   *
   * @param string $path
   * 
   * @return bool
   * 
   */
  public function empty(string $path): bool
  {
    if (!isset($this->configData[$path])) return false;
    else return empty($this->configData[$path]);
  }

  /**
   * [Description for get]
   *
   * @param string $path
   * @param null $default
   * 
   * @return mixed
   * 
   */
  public function get(string $path = '', $default = null, bool $useFullConfig = false): mixed
  {
    $path = $this->prefix . $path;

    if ($path === '') return $useFullConfig ? $this->configDataFull : $this->configData;
    else {
      $config = $useFullConfig ? $this->configDataFull : $this->configData;
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

  /**
   * [Description for getAsString]
   *
   * @param string $path
   * @param string $defaultValue
   * 
   * @return string
   * 
   */
  public function getAsString(string $path, string $defaultValue = ''): string
  {
    return (string) $this->get($path, $defaultValue);
  }

  /**
   * [Description for getAsInteger]
   *
   * @param string $path
   * @param int $defaultValue
   * 
   * @return int
   * 
   */
  public function getAsInteger(string $path, int $defaultValue = 0): int
  {
    return (int) $this->get($path, $defaultValue);
  }

  /**
   * [Description for getAsFloat]
   *
   * @param string $path
   * @param float $defaultValue
   * 
   * @return float
   * 
   */
  public function getAsFloat(string $path, float $defaultValue = 0): float
  {
    return (float) $this->get($path, $defaultValue);
  }

  /**
   * [Description for getAsBool]
   *
   * @param string $path
   * @param bool $defaultValue
   * 
   * @return bool
   * 
   */
  public function getAsBool(string $path, bool $defaultValue = false): bool
  {
    return (bool) $this->get($path, $defaultValue);
  }

  /**
   * [Description for getAsArray]
   *
   * @param string $path
   * @param array $defaultValue
   * 
   * @return array
   * 
   */
  public function getAsArray(string $path, array $defaultValue = []): array
  {
    return (array) $this->get($path, $defaultValue);
  }

  /**
   * [Description for getAsJson]
   *
   * @param string $path
   * @param array $defaultValue
   * 
   * @return array
   * 
   */
  public function getAsJson(string $path, array $defaultValue = []): array
  {
    return @json_decode($this->getAsString($path, ''), true) ?? $defaultValue;
  }





  /**
   * [Description for set]
   *
   * @param string $path
   * @param mixed $value
   * 
   * @return void
   * 
   */
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

  /**
   * [Description for save]
   *
   * @param string $path
   * @param string $value
   * 
   * @return void
   * 
   */
  public function save(string $path, string $value): void
  {
    try {
      if (!empty($path)) {
        $this->db()->execute("
          insert into `config` set `path` = :path, `value` = :value
          on duplicate key update `path` = :path, `value` = :value
        ", ['path' => $path, 'value' => $value]);

        $this->set($path, $value);
      }
    } catch (\Exception $e) {
    }
  }

  /**
   * [Description for saveForUser]
   *
   * @param string $path
   * @param string $value
   * 
   * @return void
   * 
   */
  public function saveForUser(string $path, string $value): void
  {
    $this->save('user/' . $this->authProvider()->getUserId() . '/' . $path, $value);
  }

  /**
   * [Description for delete]
   *
   * @param mixed $path
   * 
   * @return void
   * 
   */
  public function delete($path): void
  {
    try {
      if (!empty($path)) {
        $this->db()->execute("delete from `config` where `path` = :path", ['path' => $path]);
      }
    } catch (\Exception $e) {
      if ($e->getCode() == '42S02') { // Base table not found
        // do nothing
      } else {
        throw $e; // forward exception to be processed by Hubleto framework
      }
    }
  }

  /**
   * [Description for init]
   *
   * @return void
   * 
   */
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

    $this->configDataFull = $this->configData;
  }

  /**
   * [Description for filterByUser]
   *
   * @return void
   * 
   */
  public function filterByUser(): void
  {
    $idUser = $this->authProvider()->getUserId();
    if (isset($this->configData['user'][$idUser]) && is_array($this->configData['user'][$idUser])) {
      $this->configData = array_merge_recursive($this->configData, $this->configData['user'][$idUser]);
      unset($this->configData['user']);
    }
  }

}