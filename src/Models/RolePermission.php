<?php

namespace Hubleto\Framework\Models;

class RolePermission extends \Hubleto\Framework\Model
{
  public string $table = 'role_permissions';

  public function grantPermissionByString(int $idRole, string $permission): void
  {
  }
  public function grantPermissionsByString(array $idRoles, array $permissions): void
  {
    foreach ($idRoles as $idRole) {
      foreach ($permissions as $permission) $this->grantPermissionByString((int) $idRole, $permission);
    }
  }

}
