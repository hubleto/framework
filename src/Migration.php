<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Interfaces\DbInterface;
use Hubleto\Framework\Interfaces\ModelInterface;

/**
 * Default implementation of a migration for Hubleto project.
 */
abstract class Migration implements Interfaces\MigrationInterface
{
  protected DbInterface $db;
  protected ModelInterface $model;

  public function __construct(DbInterface $db, ModelInterface $model)
  {
    $this->db = $db;
    $this->model = $model;
  }
}
