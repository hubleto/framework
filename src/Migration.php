<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Interfaces\DbInterface;

/**
 * Default implementation of a migration for Hubleto project.
 */
abstract class Migration implements Interfaces\MigrationInterface
{
  protected DbInterface $db;

  public function __construct(DbInterface $db)
  {
    $this->db = $db;
  }
}
