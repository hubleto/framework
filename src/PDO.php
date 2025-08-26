<?php

namespace Hubleto\Framework;

class PDO extends CoreClass
{
  public ?\PDO $connection = null;
  public bool $isConnected = false;

  public function __construct(public Loader $main)
  {
  }

  public function connect() {
    $dbHost = $this->getConfig()->getAsString('db_host');
    $dbPort = $this->getConfig()->getAsString('db_port');
    $dbUser = $this->getConfig()->getAsString('db_user');
    $dbPassword = $this->getConfig()->getAsString('db_password');
    $dbName = $this->getConfig()->getAsString('db_name');
    $dbCodepage = $this->getConfig()->getAsString('db_codepage', 'utf8mb4');

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
        throw new \Hubleto\Framework\Exceptions\DBException('Failed to execute query: ' . $query, 0, $e);
      }
    }
  }

  public function fetchAll(string $query, array $data = [])
  {
    $stmt = $this->connection->prepare($query);
    $stmt->execute($data);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function fetchFirst(string $query, array $data = [])
  {
    $tmp = $this->fetchAll($query, $data);
    return reset($tmp);
  }

  public function startTransaction(): void
  {
    $this->execute('start transaction');
  }

  public function commit(): void
  {
    $this->execute('commit');
  }

  public function rollback(): void
  {
    $this->execute('rollback');
  }

}
