<?php

namespace Hubleto\Framework\Services;

use Hubleto\Framework\Interfaces\DbInterface;
use Hubleto\Framework\Core;

/**
 * Database abstraction layer.
 */
class Db extends Core implements DbInterface
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
        "strict"    => false,
      ], 'default');

      $this->db()->connect();
      $this->eloquent->getConnection()->beforeStartingTransaction(function () {
        if ($this->freshInstallationPlan !== null) $this->freshInstallationPlan->beforeTransaction();
      });
      $this->eloquent->getConnection()->beforeExecuting(function ($query) {
        if ($this->freshInstallationPlan !== null) $this->freshInstallationPlan->beforeQuery($query);
        $this->flushMigrationBatch();
      });
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
    $this->flushMigrationBatch();
    $stmt = $this->connection->prepare($query);
    $stmt->execute($data);
    ob_start();
    $stmt->debugDumpParams();
    var_dump(ob_get_clean());
  }

  private ?\Hubleto\Framework\Db\MigrationSqlBatch $migrationBatch = null;
  private bool $freshInstallation = false;
  private int $initialForeignKeyChecks = 1;
  private ?\PDO $originalEloquentPdo = null;
  private ?\PDO $originalEloquentReadPdo = null;
  private ?\Hubleto\Framework\Db\FreshInstallation $freshInstallationPlan = null;

  /** Enable the initialization optimization only before any tables exist. */
  public function beginFreshInstallation(): void
  {
    if ($this->freshInstallation || $this->connection->query('SHOW TABLES')->fetchColumn() !== false) {
      throw new \LogicException('A fresh installation requires an empty database.');
    }
    $this->initialForeignKeyChecks = (int) $this->connection->query('SELECT @@SESSION.foreign_key_checks')->fetchColumn();
    $this->freshInstallation = true;
    $eloquent = $this->eloquent->getConnection();
    $this->originalEloquentPdo = $eloquent->getPdo();
    $this->originalEloquentReadPdo = $eloquent->getReadPdo();
    $eloquent->setPdo($this->connection)->setReadPdo($this->connection);
    $this->freshInstallationPlan = new \Hubleto\Framework\Db\FreshInstallation($this->connection, fn(string $sql, array $data = []) => $this->executeSql($sql, $data));
  }

  public function isFreshInstallation(): bool
  {
    return $this->freshInstallation;
  }

  /** Overlap durable table creation with subsequent seed generation when mysqli is available. */
  public function startFreshPersistence(): void
  {
    if ($this->freshInstallationPlan === null) return;
    if (!extension_loaded('mysqli')) { $this->endFreshInstallation(); return; }
    $host = $this->config()->getAsString('db_host');
    $database = $this->config()->getAsString('db_name');
    if ($this->connection->query('SELECT DATABASE()')->fetchColumn() !== $database) {
      $this->endFreshInstallation();
      return;
    }
    try {
      $writer = new \mysqli(
        $host,
        $this->config()->getAsString('db_user'),
        $this->config()->getAsString('db_password'),
        $database,
        $this->config()->getAsInteger('db_port', 3306),
        $host === 'localhost' ? (ini_get('pdo_mysql.default_socket') ?: null) : null
      );
      if ($writer->connect_errno || !$writer->set_charset($this->config()->getAsString('db_codepage', 'utf8mb4'))) {
        $writer->close();
        $this->endFreshInstallation();
        return;
      }
    } catch (\mysqli_sql_exception $error) {
      // PDO-only installations retain the synchronous path if a second
      // connection cannot be opened; actual CREATE failures still propagate.
      $this->endFreshInstallation();
      return;
    }
    try {
      if (!$this->freshInstallationPlan->startPersistence($writer)) $this->endFreshInstallation();
    } catch (\Throwable $error) {
      $this->abortFreshInstallation();
      throw $error;
    }
  }

  public function endFreshInstallation(): void
  {
    $plan = $this->freshInstallationPlan;
    $this->freshInstallationPlan = null;
    try {
      if ($plan !== null) $plan->finish();
    } finally {
      try { if ($plan !== null) $plan->discard(); }
      finally {
        $this->connection->exec('SET SESSION foreign_key_checks=' . $this->initialForeignKeyChecks);
        $this->freshInstallation = false;
        $this->restoreEloquentConnection();
      }
    }
  }

  private function restoreEloquentConnection(): void
  {
    $connection = $this->eloquent->getConnection();
    if ($this->originalEloquentPdo !== null && $connection->transactionLevel() === 0) {
      $connection->setPdo($this->originalEloquentPdo)->setReadPdo($this->originalEloquentReadPdo);
      $this->originalEloquentPdo = null;
      $this->originalEloquentReadPdo = null;
    }
  }

  /** Cancel a failed installation, draining pending DDL before dropping temporary tables. */
  public function abortFreshInstallation(): void
  {
    $plan = $this->freshInstallationPlan;
    $this->freshInstallationPlan = null;
    try { if ($plan !== null) $plan->discard(); }
    finally {
      if ($this->freshInstallation) $this->connection->exec('SET SESSION foreign_key_checks=' . $this->initialForeignKeyChecks);
      $this->freshInstallation = false;
      $this->restoreEloquentConnection();
    }
  }

  public function runMigrationBatch(string $kind, callable $migration): void
  {
    if (!in_array($kind, ['schema', 'foreignKeys'], true)) throw new \InvalidArgumentException('Unknown migration batch kind.');
    if ($this->freshInstallationPlan !== null) {
      try { $migration(); }
      catch (\Throwable $e) { $this->abortFreshInstallation(); throw $e; }
      return;
    }
    $this->flushMigrationBatch();
    $previous = $this->migrationBatch;
    $this->migrationBatch = new \Hubleto\Framework\Db\MigrationSqlBatch(
      $kind,
      fn(string $table, array $clauses, string $kind) => $this->executeMigrationBatch($table, $clauses, $kind)
    );
    try {
      $migration();
      $this->flushMigrationBatch();
    } finally {
      $this->migrationBatch = $previous;
    }
  }

  private function flushMigrationBatch(): void
  {
    if ($this->freshInstallationPlan !== null) return;
    $batch = $this->migrationBatch;
    if ($batch === null) return;
    $this->migrationBatch = null;
    try {
      $batch->flush();
    } finally {
      $this->migrationBatch = $batch;
    }
  }

  private function executeMigrationBatch(string $table, array $clauses, string $kind): void
  {
    $batch = $this->migrationBatch;
    $this->migrationBatch = null;
    try {
      $this->execute('ALTER TABLE ' . $table . ' ' . implode(', ', $clauses));
    } finally {
      $this->migrationBatch = $batch;
    }
  }

  public function execute(string $query, array $data = []): void
  {
    if ($this->freshInstallationPlan !== null) {
      try { $this->freshInstallationPlan->execute($query, $data); }
      catch (\Throwable $e) { $this->abortFreshInstallation(); throw $e; }
      return;
    }
    if ($this->migrationBatch !== null) {
      if (!$data && $this->migrationBatch->add($query)) return;
      $this->flushMigrationBatch();
    }
    $this->executeSql($query, $data);
  }

  private function executeSql(string $query, array $data = []): void
  {
    if (!empty($query)) {
      try {
        $stmt = $this->connection->prepare(trim($query));
        $stmt->execute($data);
        // Multi-statement migrations must complete before timing, batching or
        // recording their version. Later statements can fail independently.
        while ($stmt->nextRowset()) {}
        $stmt->closeCursor();
      } catch (\Exception $e) {
        throw new \Hubleto\Framework\Exceptions\DBException(
          'Failed to execute query: ' . $query . '\n' . print_r(isset($stmt) ? $stmt->errorInfo() : $e->getMessage(), true),
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
    if ($this->freshInstallationPlan !== null) $this->freshInstallationPlan->beforeQuery($query);
    $this->flushMigrationBatch();
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
