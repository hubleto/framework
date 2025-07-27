<?php

namespace Hubleto\Framework\Models;

/**
 * Model for storing list of user roles. Stored in 'roles' SQL table.
 *
 * @package DefaultModels
 */
class UserRole extends \Hubleto\Framework\Model {
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
      'name' => new \Hubleto\Framework\Db\Column\Varchar($this, 'Role name'),
    ]);
  }
}
