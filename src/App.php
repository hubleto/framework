<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Interfaces\AppManagerInterface;

class App extends Core implements Interfaces\AppInterface
{
  public const DEFAULT_INSTALLATION_CONFIG = [
    'sidebarOrder' => 500,
  ];

  public const APP_TYPE_COMMUNITY = 'community';
  public const APP_TYPE_PREMIUM = 'premium';
  public const APP_TYPE_EXTERNAL = 'external';

  public array $manifest = [];

  public bool $enabled = false;
  public bool $canBeDisabled = true;

  public bool $permittedForAllUsers = false;

  public string $srcFolder = '';
  public string $viewNamespace = '';
  public string $namespace = '';
  public string $fullName = '';

  public bool $isActivated = false;
  public bool $hasCustomSettings = false;

  /** @var array<int, array<\Hubleto\Framework\App, array>> */
  private array $settings = [];

  public array $searchSwitches = [];

  public function __construct()
  {
    parent::__construct();

    $reflection = new \ReflectionClass($this);

    $this->srcFolder = pathinfo((string) $reflection->getFilename(), PATHINFO_DIRNAME);
    $this->namespace = $reflection->getNamespaceName();
    $this->fullName = $reflection->getName();
    $this->translationContext = trim(str_replace('\\', '/', $this->fullName), '/');

    $this->viewNamespace = $this->namespace;
    $this->viewNamespace = str_replace('\\', ':', $this->viewNamespace);

    $manifestFile = $this->srcFolder . '/manifest.yaml';
    if (is_file($manifestFile)) {
      $this->manifest = (array) \Symfony\Component\Yaml\Yaml::parse((string) file_get_contents($manifestFile));
    } else {
      $this->manifest = [];
    }

    $this->validateManifest();

  }

  /**
   * [Description for validateManifest]
   *
   * @return [type]
   * 
   */
  public function validateManifest()
  {
    $missing = [];
    if (empty($this->manifest['namespace'])) {
      $missing[] = 'namespace';
    }
    if (empty($this->manifest['appType'])) {
      $missing[] = 'appType';
    }
    if (empty($this->manifest['rootUrlSlug'])) {
      $missing[] = 'rootUrlSlug';
    }
    if (empty($this->manifest['name'])) {
      $missing[] = 'name';
    }
    if (empty($this->manifest['highlight'])) {
      $missing[] = 'highlight';
    }
    if (empty($this->manifest['icon'])) {
      $missing[] = 'icon';
    }

    if (count($missing) > 0) {
      throw new \Exception("{$this->fullName}: Some properties are missing in manifest (" . join(", ", $missing) . ").");
    }

    if (!str_starts_with($this->manifest['namespace'], 'HubletoApp')) {
      throw new \Exception("{$this->fullName}: Namespace must start with 'HubletoApp'.");
    }
  }

  public function init(): void
  {
    $this->manifest['nameTranslated'] = $this->translate($this->manifest['name'], [], 'manifest');
    $this->manifest['highlightTranslated'] = $this->translate($this->manifest['highlight'], [], 'manifest');

    $this->getRenderer()->addNamespace($this->srcFolder . '/Views', $this->viewNamespace);
  }

  public function onBeforeRender(): void
  {
  }

  public function hook(string $hook): void
  {
  }

  public function getRootUrlSlug(): string
  {
    return $this->manifest['rootUrlSlug'] ?? '';
  }

  public function getNotificationsCount(): int
  {
    return 0;
  }

  public function translate(string $string, array $vars = [], string $context = 'root'): string
  {
    return $this->getTranslator()->translate($string, $vars, $this->fullName . '::' . $context);
  }

  public function installTables(int $round): void
  {
    if ($round == 1) {
      // to be overriden
    }
  }

  public function getAvailableControllerClasses(): array
  {
    $controllerClasses = [];

    $controllersFolder = $this->srcFolder . '/Controllers';
    if (is_dir($controllersFolder)) {
      $controllers = Helper::scanDirRecursively($controllersFolder);
      foreach ($controllers as $controller) {
        $cClass = $this->namespace . '/Controllers/' . $controller;
        $cClass = str_replace('/', '\\', $cClass);
        $cClass = str_replace('.php', '', $cClass);
        if (class_exists($cClass)) {
          $controllerClasses[] = $cClass;
        }
      }
    }

    return $controllerClasses;
  }

  public function getAvailableModelClasses(): array
  {
    $modelClasses = [];

    $modelsFolder = $this->srcFolder . '/Models';
    if (is_dir($modelsFolder)) {
      $models = Helper::scanDirRecursively($modelsFolder);
      foreach ($models as $model) {
        $mClass = $this->namespace . '/Models/' . $model;
        $mClass = str_replace('/', '\\', $mClass);
        $mClass = str_replace('.php', '', $mClass);
        if (class_exists($mClass)) {
          try {
            $mObj = $this->getService($mClass);
            $modelClasses[] = $mClass;
          } catch (\Throwable) {
          }
        }
      }
    }

    return $modelClasses;

  }

  public function installDefaultPermissions(): void
  {
    $permissions = [];

    $controllersFolder = $this->srcFolder . '/Controllers';
    if (is_dir($controllersFolder)) {
      $controllers = Helper::scanDirRecursively($controllersFolder);
      foreach ($controllers as $controller) {
        $cClass = $this->namespace . '/Controllers/' . $controller;
        $cClass = str_replace('/', '\\', $cClass);
        $cClass = str_replace('.php', '', $cClass);
        if (class_exists($cClass)) {
          $cObj = $this->getService($cClass);
          $permissions[] = $cObj->permission;
        }
      }
    }

    $mPermission = $this->getModel(\HubletoApp\Community\Settings\Models\Permission::class);

    foreach ($permissions as $permission) {
      $mPermission->record->recordCreate([
        "permission" => $permission
      ]);
    }
  }

  public function assignPermissionsToRoles(): void
  {

    /** @var \Hubleto\Framework\Models\UserRole */
    $mUserRole = $this->getPermissionsManager()->createUserRoleModel();

    /** @var \Hubleto\Framework\Models\RolePermission */
    $mRolePermission = $this->getPermissionsManager()->createRolePermissionModel();

    $userRoles = $mUserRole->record->get()->toArray();
    foreach ($userRoles as $role) {
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Table/Describe');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Form/Describe');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Get');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Delete');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/GetList');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Lookup');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Save');
      $mRolePermission->grantPermissionByString($role['id'], 'HubletoMain/Core/Api/GetTableColumnsCustomize');
      $mRolePermission->grantPermissionByString($role['id'], 'HubletoMain/Core/Api/SaveTableColumnsCustomize');
      $mRolePermission->grantPermissionByString($role['id'], 'HubletoMain/Core/Api/GetTemplateChartData');
    }

    $controllerClasses = $this->getAvailableControllerClasses();
    foreach ($controllerClasses as $controllerClass) {
      $cObj = $this->getService($controllerClass);
      foreach ($userRoles as $role) {
        $mRolePermission->grantPermissionByString($role['id'], $cObj->fullName);
      }
    }

  }

  public function generateDemoData(): void
  {
    // to be overriden
  }

  public function renderSecondSidebar(): string
  {
    return '';
  }

  /**
   * Implements fulltext search functionality for the app
   *
   * @param array $expressions List of expressions to be searched and glued with logical 'or'.
   * 
   * @return array
   * 
   */
  public function search(array $expressions): array
  {
    return [];
  }

  public function addSetting(\Hubleto\Framework\Interfaces\AppInterface $app, array $setting): void
  {
    $this->settings[] = [$app, $setting];
  }

  public function getSettings(): array
  {
    $settings = [];
    foreach ($this->settings as $setting) {
      $settings[] = $setting[1];
    }

    $titles = array_column($settings, 'title');
    array_multisort($titles, SORT_ASC, $settings);
    return $settings;
  }

  public function getFullConfigPath(string $path): string
  {
    return 'apps/' . $this->getAppManager()->getAppNamespaceForConfig($this->namespace) . '/' . $path;
  }

  public function saveConfig(string $path, string $value = ''): void
  {
    $this->getConfig()->save($this->getFullConfigPath($path), $value);
  }

  public function saveConfigForUser(string $path, string $value = ''): void
  {
    $this->getConfig()->saveForUser($this->getFullConfigPath($path), $value);
  }


  public function configAsString(string $path, string $defaultValue = ''): string
  {
    return (string) $this->getConfig()->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsInteger(string $path, int $defaultValue = 0): int
  {
    return (int) $this->getConfig()->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsFloat(string $path, float $defaultValue = 0): float
  {
    return (float) $this->getConfig()->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsBool(string $path, bool $defaultValue = false): bool
  {
    return (bool) $this->getConfig()->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsArray(string $path, array $defaultValue = []): array
  {
    return (array) $this->getConfig()->get($path, $defaultValue);
  }

  public function setConfigAsString(string $path, string $value = ''): void
  {
    $this->getConfig()->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsInteger(string $path, int $value = 0): void
  {
    $this->getConfig()->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsFloat(string $path, float $value = 0): void
  {
    $this->getConfig()->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsBool(string $path, bool $value = false): void
  {
    $this->getConfig()->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsArray(string $path, array $value = []): void
  {
    $this->getConfig()->set($this->getFullConfigPath($path), $value);
  }



  public function dangerouslyInjectDesktopHtmlContent(string $where): string
  {
    return '';
  }

  public function addSearchSwitch(string $switch, string $name): void
  {
    $this->searchSwitches[$switch] = $name;
  }

  public function canHandleSearchSwith(string $switch): bool
  {
    return isset($this->searchSwitches[$switch]);
  }

  public function collectExtendibles(string $extendibleName): array
  {
    $items = [];
    foreach ($this->getAppManager()->getEnabledApps() as $app) {
      try {
        $extendible = $this->getService($app->namespace . '\\Extendibles\\' . $extendibleName);
        $extendible->app = $app;
        if ($extendible instanceof Extendible) {
          $items = array_merge($items, $extendible->getItems());
        }
      } catch (\Throwable $e) {
        // do nothing
      }
    }

    return $items;
  }

}
