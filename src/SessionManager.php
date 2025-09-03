<?php

namespace Hubleto\Framework;

/**
 * Default session manager for Hubleto project.
 */
class SessionManager extends Core implements Interfaces\SessionManagerInterface
{

  private string $salt = '';

  public function __construct()
  {
    parent::__construct();

    if (isset($_SESSION) && is_array($_SESSION) && !is_array($_SESSION[$this->salt])) $_SESSION[$this->salt] = [];

    $this->salt = $this->config()->getAsString('sessionSalt');
  }

  /**
   * [Description for getSalt]
   *
   * @return string
   * 
   */
  public function getSalt(): string
  {
    return $this->salt;
  }

  public function start(bool $persist, array $options = []): void
  {
    if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
      if (empty($this->salt)) throw new \Exception('Hubleto: Cannot start session, salt is empty.');

      session_id();
      session_name($this->salt);

      if ($persist) {
        $options['cookie_lifetime'] = 2592000; // 30 days (1 month)
        $options['gc_maxlifetime'] = 2592000; // 30 days (1 month)
      }

      session_start($options);

      define('_SESSION_ID', session_id());
    }
  }

  public function prolongSession(int $seconds = 2592000): void
  {
    if (session_status() == PHP_SESSION_ACTIVE) {
      setcookie(
        session_name(),
        session_id(),
        time() + $seconds,
        ini_get('session.cookie_path'),
        ini_get('session.cookie_domain'),
        ini_get('session.cookie_secure'),
        ini_get('session.cookie_httponly')
      );
    }
  }

  public function stop(): void
  {
    if (session_status() == PHP_SESSION_ACTIVE) {
      session_write_close();
    }
  }

  public function set(string $path, mixed $value, string $key = '')
  {
    if (empty($key)) $key = $this->salt;
    if (!isset($_SESSION[$key])) $_SESSION[$key] = [];
    $_SESSION[$key][$path] = $value;
  }

  public function get(string $path = '', string $key = ''): mixed
  {
    if (empty($key)) $key = $this->salt;
    if ($path == '') return $_SESSION[$key] ?? [];
    else return $_SESSION[$key][$path] ?? null;
  }

  public function push(string $path, mixed $value): void
  {
    if (!is_array($_SESSION[$this->salt][$path])) $_SESSION[$this->salt][$path] = [];
    $_SESSION[$this->salt][$path][] = $value;
  }

  public function isset(string $path): bool
  {
    return isset($_SESSION[$this->salt][$path]);
  }

  public function unset(string $path): void
  {
    if ($this->isset($path)) unset($_SESSION[$this->salt][$path]);
  }

  public function clear(): void
  {
    unset($_SESSION[$this->salt]);
  }

}