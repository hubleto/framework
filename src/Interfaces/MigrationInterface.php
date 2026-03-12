<?php

namespace Hubleto\Framework\Interfaces;

interface MigrationInterface
{

  public function upgradeSchema(): void;

  public function downgradeSchema(): void;

  public function upgradeForeignKeys(): void;

  public function downgradeForeignKeys(): void;

}
