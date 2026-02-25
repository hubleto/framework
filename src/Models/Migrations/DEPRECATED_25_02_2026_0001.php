<?php

namespace Hubleto\Framework\Models\Migrations;

use http\Exception\BadMethodCallException;
use Hubleto\Framework\Migration;

class DEPRECATED_25_02_2026_0001 extends Migration
{

  public function installTables(): void
  {
    foreach ($this->model->getSqlCreateTableCommands() as $sql) {
      $this->db->execute($sql);
    }
    foreach ($this->model->getSqlCreateIndexesCommands() as $sql) {
      $this->db->execute($sql);
    }
  }

  public function uninstallTables(): void
  {
    $this->db->execute("DROP TABLE IF EXISTS `:table`;", ['table' => $this->model->table]);
  }

  public function installForeignKeys(): void
  {
    foreach ($this->model->getSqlCreateIndexesCommands() as $sql) {
      $this->db->execute($sql);
    }
  }

  public function uninstallForeignKeys(): void
  {
    throw new BadMethodCallException();
  }
}