<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Models;

/**
 * Model for storing list of user roles. Stored in 'roles' SQL table.
 *
 * @package DefaultModels
 */
class UserRole extends \ADIOS\Core\Model {
  const ADMINISTRATOR = 1;

  const USER_ROLES = [
    self::ADMINISTRATOR => 'ADMINISTRATOR',
  ];

  public string $recordManagerClass = RecordManagers\UserRole::class;
  public string $table = 'user_roles';
  public ?string $lookupSqlValue = "{%TABLE%}.name";

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'name' => new \ADIOS\Core\Db\Column\Varchar($this, 'Role name'),
    ]);
  }
}
