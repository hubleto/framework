<?php

namespace Hubleto\Framework;

class Permissions
{

  protected bool $grantAllPermissions = false;
  protected array $permissions = [];
  public array $administratorRoles = [];
  public array $administratorTypes = [];

  public function __construct(public \Hubleto\Framework\Loader $main)
  {
  }

  public function init(): void
  {
    $this->permissions = $this->loadPermissions();
    $this->expandPermissionGroups();
    $this->administratorRoles = $this->loadAdministratorRoles();
    $this->administratorTypes = $this->loadAdministratorTypes();
  }

  public function createUserRoleModel(): Model
  {
    return null; //new \HubletoApp\Community\Settings\Models\UserRole($this->main);
  }

  public function DANGEROUS__grantAllPermissions()
  {
    $this->grantAllPermissions = true;
  }

  public function revokeGrantAllPermissions()
  {
    $this->grantAllPermissions = false;
  }

  public function loadAdministratorRoles(): array
  {
    return [];
  }

  public function loadAdministratorTypes(): array
  {
    return [];
  }

  public function expandPermissionGroups() {
    foreach ($this->permissions as $idUserRole => $permissionsByRole) {
      foreach ($permissionsByRole as $permission) {
        if (strpos($permission, ':') !== FALSE) {
          list($pGroup, $pGroupItems) = explode(':', $permission);
          if (strpos($pGroupItems, ',') !== FALSE) {
            $pGroupItemsArr = explode(',', $pGroupItems);
            if (count($pGroupItemsArr) > 1) {
              foreach ($pGroupItemsArr as $item) {
                $this->permissions[$idUserRole][] = $pGroup . ':' . $item;
              }
            }
          }
        }
      }
    }
  }

  public function set(string $permission, int $idUserRole, bool $isEnabled)
  {
    $this->main->getConfig()->save(
      "permissions/{$idUserRole}/".str_replace("/", ":", $permission),
      $isEnabled ? "1" : "0"
    );
  }

  public function hasRole(int|string $role) {
    if (is_string($role)) {
      $userRoleModel = $this->createUserRoleModel();
      if ($userRoleModel) {
        $idUserRoleByRoleName = array_flip($userRoleModel::USER_ROLES);
        $idRole = (int) $idUserRoleByRoleName[$role];
      } else {
        $idRole = 0;
      }
    } else {
      $idRole = (int) $role;
    }

    return in_array($idRole, $this->main->auth->getUserRoles());
  }

  public function grantedForRole(string $permission, int|string $userRole): bool
  {
    if (empty($permission)) return TRUE;

    $granted = (bool) in_array($permission, (array) ($this->permissions[$userRole] ?? []));

    if (!$granted) {
    }

    return $granted;
  }

  public function granted(string $permission, array $userRoles = [], int $userType = 0): bool
  {
    if ($this->grantAllPermissions) {
      return true;
    } else {
      if (empty($permission)) return true;
      if (count($userRoles) == 0) $userRoles = $this->main->auth->getUserRoles();
      if ($userType == 0) $userType = $this->main->auth->getUserType();

      $granted = false;

      if (count(array_intersect($this->administratorRoles, $userRoles)) > 0) $granted = true;
      if (in_array($userType, $this->administratorTypes)) $granted = true;

      // check if the premission is granted for one of the roles of the user
      if (!$granted) {
        foreach ($userRoles as $userRole) {
          $granted = $this->grantedForRole($permission, $userRole);
          if ($granted) break;
        }
      }

      // check if the premission is granted "globally" (for each role)
      if (!$granted) {
        $granted = $this->grantedForRole($permission, 0);
      }

      return $granted;
    }

  }

  public function check(string $permission) {
    if (!$this->granted($permission) && !$this->granted(str_replace('\\', '/', $permission))) {
      throw new Exceptions\NotEnoughPermissionsException("Not enough permissions ({$permission}).");
    }
  }

  /**
  * @return array<int, array<int, string>>
  */
  public function loadPermissions(): array
  {
    return [];
  }

  public function isAppPermittedForActiveUser(\Hubleto\Framework\Interfaces\AppInterface $app)
  {
    $userRoles = $this->main->auth->getUserRoles();
    $userType = $this->main->auth->getUserType();

    if (
      $this->grantAllPermissions
      || $app->permittedForAllUsers
      || in_array($userType, $this->administratorTypes)
      || count(array_intersect($this->administratorRoles, $userRoles)) > 0
    ) {
      return true;
    }

    $user = $this->main->auth->getUser();
    $userApps = @json_decode($user['apps'], true);

    return is_array($userApps) && in_array($app->namespace, $userApps);
  }
}
