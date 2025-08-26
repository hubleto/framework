<?php

namespace Hubleto\Framework\Models;

class RolePermission extends \Hubleto\Framework\Model
{
  public string $table = 'role_permissions';

  public function grantPermissionByString(int $idRole, string $permission): void
  {
  }
}
