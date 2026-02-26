<?php

namespace Hubleto\Framework\Models\Migrations;

use http\Exception\BadMethodCallException;
use Hubleto\Framework\Lib\ModelSQLCommandsGenerator;
use Hubleto\Framework\Migration;

class DEPRECATED_25_02_2026_0001 extends Migration
{
  protected ModelSQLCommandsGenerator $sqlGenerator;

  public function __construct($db, $model) {
    parent::__construct($db, $model);
    $this->sqlGenerator = new ModelSQLCommandsGenerator();
  }

  public function installTables(): void
  {
    foreach ($this->sqlGenerator->getSqlCreateTableCommands($this->model) as $sql) {
      $this->db->execute($sql);
    }
    foreach ($this->sqlGenerator->getSqlCreateIndexesCommands($this->model) as $sql) {
      $this->db->execute($sql);
    }
  }

  public function uninstallTables(): void
  {
    foreach ($this->model->getSqlDropTableIfExists() as $sql) {
      $this->db->execute($sql);
    }
  }

  public function installForeignKeys(): void
  {
    foreach ($this->sqlGenerator->getSqlCreateForeignKeysCommands($this->model) as $sql) {
      $this->db->execute($sql);
    }
  }

  public function uninstallForeignKeys(): void
  {
    foreach ($this->sqlGenerator->getSqlDropForeignKeysCommands($this->model) as $sql) {
      $this->db->execute($sql);
    }
  }
}