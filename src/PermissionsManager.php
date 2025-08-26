<?php

namespace Hubleto\Framework;

class PermissionsManager extends CoreClass implements Interfaces\PermissionsManagerInterface
{

  protected bool $grantAllPermissions = false;
  protected array $permissionsData = [];
  public array $administratorRoles = [];
  public array $administratorTypes = [];

  protected string $permission = '';

  public function init(): void
  {
    $this->permissionsData = $this->loadPermissions();
    $this->expandPermissionGroups();
    $this->administratorRoles = $this->loadAdministratorRoles();
    $this->administratorTypes = $this->loadAdministratorTypes();
  }

  public function getPermission(): string
  {
    return $this->permission;
  }

  public function setPermission(string $permission): void
  {
    $this->permission = $permission;
  }

  public function createUserRoleModel(): Model
  {
    return $this->getService(\Hubleto\Framework\Models\UserRole::class);
  }

  public function DANGEROUS__grantAllPermissions(): void
  {
    $this->grantAllPermissions = true;
  }

  public function revokeGrantAllPermissions(): void
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

  public function expandPermissionGroups(): void
  {
    foreach ($this->permissionsData as $idUserRole => $permissionsByRole) {
      foreach ($permissionsByRole as $permission) {
        if (strpos($permission, ':') !== FALSE) {
          list($pGroup, $pGroupItems) = explode(':', $permission);
          if (strpos($pGroupItems, ',') !== FALSE) {
            $pGroupItemsArr = explode(',', $pGroupItems);
            if (count($pGroupItemsArr) > 1) {
              foreach ($pGroupItemsArr as $item) {
                $this->permissionsData[$idUserRole][] = $pGroup . ':' . $item;
              }
            }
          }
        }
      }
    }
  }

  public function set(string $permission, int $idUserRole, bool $isEnabled)
  {
    $this->getConfig()->save(
      "permissions/{$idUserRole}/".str_replace("/", ":", $permission),
      $isEnabled ? "1" : "0"
    );
  }

  public function hasRole(int|string $role): bool
  {
    if (is_string($role)) {
      $userRoleModel = $this->createUserRoleModel();
      if ($userRoleModel) {
        /** @disregard P1012 */
        $idUserRoleByRoleName = array_flip($userRoleModel::USER_ROLES);
        $idRole = (int) $idUserRoleByRoleName[$role];
      } else {
        $idRole = 0;
      }
    } else {
      $idRole = (int) $role;
    }

    return in_array($idRole, $this->getAuthProvider()->getUserRoles());
  }

  public function grantedForRole(string $permission, int|string $userRole): bool
  {
    if (empty($permission)) return TRUE;

    $granted = (bool) in_array($permission, (array) ($this->permissionsData[$userRole] ?? []));

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
      if (count($userRoles) == 0) $userRoles = $this->getAuthProvider()->getUserRoles();
      if ($userType == 0) $userType = $this->getAuthProvider()->getUserType();

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

  public function checkPermission(): void
  {
    $this->check($this->permission);
  }

  public function check(string $permission): void
  {
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

  public function isAppPermittedForActiveUser(\Hubleto\Framework\Interfaces\AppInterface $app): bool
  {
    $userRoles = $this->getAuthProvider()->getUserRoles();
    $userType = $this->getAuthProvider()->getUserType();

    if (
      $this->grantAllPermissions
      || $app->permittedForAllUsers
      || in_array($userType, $this->administratorTypes)
      || count(array_intersect($this->administratorRoles, $userRoles)) > 0
    ) {
      return true;
    }

    $user = $this->getAuthProvider()->getUser();
    $userApps = @json_decode($user['apps'], true);

    return is_array($userApps) && in_array($app->namespace, $userApps);
  }
}
