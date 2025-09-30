<?php

namespace Hubleto\Framework;

/**
 * Database abstraction layer.
 */
class Db extends Core implements Interfaces\DbInterface
{
  public ?\PDO $connection = null;
  public bool $isConnected = false;

  public \Illuminate\Database\Capsule\Manager $eloquent;

  /**
   * [Description for init]
   *
   * @return [type]
   * 
   */
  public function init()
  {
    $dbHost = $this->config()->getAsString('db_host', '');
    $dbPort = $this->config()->getAsInteger('db_port', 3306);
    $dbName = $this->config()->getAsString('db_name', '');
    $dbUser = $this->config()->getAsString('db_user', '');
    $dbPassword = $this->config()->getAsString('db_password', '');

    if (!empty($dbHost) && !empty($dbPort) && !empty($dbUser)) {
      $this->eloquent = new \Illuminate\Database\Capsule\Manager;
      $this->eloquent->setAsGlobal();
      $this->eloquent->bootEloquent();
      $this->eloquent->addConnection([
        "driver"    => "mysql",
        "host"      => $dbHost,
        "port"      => $dbPort,
        "database"  => $dbName ?? '',
        "username"  => $dbUser,
        "password"  => $dbPassword,
        "charset"   => 'utf8mb4',
        "collation" => 'utf8mb4_unicode_ci',
      ], 'default');

      $this->db()->connect();
    }
  }

  /**
   * [Description for connect]
   *
   * @return [type]
   * 
   */
  public function connect() {
    $dbHost = $this->config()->getAsString('db_host');
    $dbPort = $this->config()->getAsString('db_port');
    $dbUser = $this->config()->getAsString('db_user');
    $dbPassword = $this->config()->getAsString('db_password');
    $dbName = $this->config()->getAsString('db_name');
    $dbCodepage = $this->config()->getAsString('db_codepage', 'utf8mb4');

    if (!empty($dbHost)) {
      if (empty($dbName)) {
        $this->connection = new \PDO(
          "mysql:host={$dbHost};port={$dbPort};charset={$dbCodepage}",
          $dbUser,
          $dbPassword
        );

        $this->isConnected = true;
      } else {
        $this->connection = new \PDO(
          "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCodepage}",
          $dbUser,
          $dbPassword
        );

        $this->isConnected = true;
      }
    }

  }

  /**
   * [Description for debugQuery]
   *
   * @param mixed $query
   * @param array $data
   * 
   * @return [type]
   * 
   */
  public function debugQuery($query, $data = []) {
    $stmt = $this->connection->prepare($query);
    $stmt->execute($data);
    ob_start();
    $stmt->debugDumpParams();
    var_dump(ob_get_clean());
  }

  public function execute(string $query, array $data = []): void
  {
    if (!empty($query)) {
      try {
        $stmt = $this->connection->prepare(trim($query));
        $stmt->execute($data);
      } catch (\Exception $e) {
        throw new \Hubleto\Framework\Exceptions\DBException(
          'Failed to execute query: ' . $query . '\n' . print_r($stmt->errorInfo(), true),
          0,
          $e
        );
      }
    }
  }

  /**
   * [Description for fetchAll]
   *
   * @param string $query
   * @param array $data
   * 
   * @return [type]
   * 
   */
  public function fetchAll(string $query, array $data = [])
  {
    try {
      $stmt = $this->connection->prepare($query);
      $stmt->execute($data);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
      throw new \Hubleto\Framework\Exceptions\DBException($e->getMessage() . '\nQuery: ' . $query);
    }
  }

  /**
   * [Description for fetchFirst]
   *
   * @param string $query
   * @param array $data
   * 
   * @return [type]
   * 
   */
  public function fetchFirst(string $query, array $data = [])
  {
    $tmp = $this->fetchAll($query, $data);
    return reset($tmp);
  }

  /**
   * [Description for startTransaction]
   *
   * @return void
   * 
   */
  public function startTransaction(): void
  {
    $this->execute('start transaction');
  }

  /**
   * [Description for commit]
   *
   * @return void
   * 
   */
  public function commit(): void
  {
    $this->execute('commit');
  }

  /**
   * [Description for rollback]
   *
   * @return void
   * 
   */
  public function rollback(): void
  {
    $this->execute('rollback');
  }

}
