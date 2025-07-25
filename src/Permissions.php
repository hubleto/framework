<?php

namespace Hubleto\Framework;

use HubletoApp\Community\Settings\Models\Permission;
use HubletoApp\Community\Settings\Models\RolePermission;
use HubletoApp\Community\Settings\Models\UserRole;

class Permissions
{
  public \Hubleto\Framework\Loader $main;

  protected bool $grantAllPermissions = false;
  protected array $permissions = [];
  public array $administratorRoles = [];

  public function __construct(\Hubleto\Framework\Loader $main)
  {
    $this->main = $main;
  }

  public function init(): void
  {
    $this->permissions = $this->loadPermissions();
    $this->expandPermissionGroups();
    $this->administratorRoles = $this->loadAdministratorRoles();
  }

  public function createUserRoleModel(): Model
  {
    return new \HubletoApp\Community\Settings\Models\UserRole($this->main);
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
    if (!isset($this->main->pdo) || !$this->main->pdo->isConnected) {
      return [];
    }
    $mUserRole = $this->main->di->create(UserRole::class);
    $administratorRoles = Helper::pluck('id', $this->main->pdo->fetchAll("select id from `{$mUserRole->table}` where grant_all = 1"));
    return $administratorRoles;
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
    $this->main->config->save(
      "permissions/{$idUserRole}/".str_replace("/", ":", $permission),
      $isEnabled ? "1" : "0"
    );
  }

  public function hasRole(int|string $role) {
    if (is_string($role)) {
      $userRoleModel = $this->createUserRoleModel();
      $idUserRoleByRoleName = array_flip($userRoleModel::USER_ROLES);
      $idRole = (int) $idUserRoleByRoleName[$role];
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

  public function granted(string $permission, array $userRoles = []): bool
  {
    if ($this->grantAllPermissions) {
      return true;
    } else {
      if (empty($permission)) return true;
      if (count($userRoles) == 0) $userRoles = $this->main->auth->getUserRoles();

      $granted = false;

      if (count(array_intersect($this->administratorRoles, $userRoles)) > 0) $granted = true;

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
    $permissions = [];
    foreach ($this->main->config->getAsArray('permissions') as $idUserRole => $permissionsByRole) {
      $permissions[$idUserRole] = [];
      foreach ($permissionsByRole as $permissionPath => $isEnabled) {
        if ((bool) $isEnabled) {
          $permissions[$idUserRole][] = str_replace(":", "/", $permissionPath);
        }
      }
      $permissions[$idUserRole] = array_unique($permissions[$idUserRole]);
    }

    if (isset($this->main->pdo) && $this->main->pdo->isConnected) {
      $mUserRole = $this->main->di->create(UserRole::class);

      $idCommonUserRoles = Helper::pluck('id', $this->main->pdo->fetchAll("select id from `{$mUserRole->table}` where grant_all = 0"));

      foreach ($idCommonUserRoles as $idCommonRole) {
        $idCommonRole = (int) $idCommonRole;

        $mRolePermission = $this->main->di->create(RolePermission::class);

        /** @var array<int, array> */
        $rolePermissions = (array) $mRolePermission->record
          ->selectRaw("role_permissions.*,permissions.permission")
          ->where("id_role", $idCommonRole)
          ->join("permissions", "role_permissions.id_permission", "permissions.id")
          ->get()
          ->toArray()
        ;

        foreach ($rolePermissions as $key => $rolePermission) {
          $permissions[$idCommonRole][] = (string) $rolePermission['permission'];
        }
      }
    }

    return $permissions;
  }

  public function isAppPermittedForActiveUser(\Hubleto\Framework\App $app)
  {
    $userRoles = $this->main->auth->getUserRoles();

    if (
      $this->grantAllPermissions
      || $app->permittedForAllUsers
      || count(array_intersect($this->administratorRoles, $userRoles)) > 0
    ) {
      return true;
    }

    $user = $this->main->auth->getUser();
    $userApps = @json_decode($user['apps'], true);

    return is_array($userApps) && in_array($app->namespace, $userApps);
  }
}
