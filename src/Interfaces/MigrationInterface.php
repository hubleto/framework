<?php

namespace Hubleto\Framework\Interfaces;

interface MigrationInterface
{

  public function installTables(): void;

  public function uninstallTables(): void;

  public function installIndexes(): void;

  public function uninstallIndexes(): void;

}
