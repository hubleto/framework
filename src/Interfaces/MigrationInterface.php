<?php

namespace Hubleto\Framework\Interfaces;

interface MigrationInterface
{

  public function installTables(): void;

  public function uninstallTables(): void;

  public function installForeignKeys(): void;

  public function uninstallForeignKeys(): void;

}
