<?php

namespace Hubleto\Framework\Models;

class UserHasRole extends \Hubleto\Framework\Model {

  public string $table = "user_has_roles";
  public string $recordManagerClass = RecordManagers\UserHasRole::class;
  public bool $isJunctionTable = FALSE;

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_user' => new \Hubleto\Framework\Db\Column\Lookup($this, 'User', User::class),
      'id_role' => new \Hubleto\Framework\Db\Column\Lookup($this, 'Role', UserRole::class),
    ]);
  }
}
