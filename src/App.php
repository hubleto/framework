<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Interfaces\AppManagerInterface;

/**
 * Encapsulation for Hubleto app.
 */
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
  public string $shortName = '';

  public bool $isActivated = false;
  public bool $hasCustomSettings = false;

  public string $sidebarView = '';

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

    $tmp = str_replace('\\Loader', '', $this->fullName);
    $this->shortName = substr($tmp, strrpos($tmp, '\\') + 1);

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

    if (!str_starts_with($this->manifest['namespace'], 'Hubleto\\App')) {
      throw new \Exception("{$this->fullName}: Namespace must start with 'Hubleto\\App'.");
    }
  }

  /**
   * [Description for init]
   *
   * @return void
   * 
   */
  public function init(): void
  {
    $this->manifest['nameTranslated'] = $this->translate($this->manifest['name'], [], 'manifest');
    $this->manifest['highlightTranslated'] = $this->translate($this->manifest['highlight'], [], 'manifest');

    $this->renderer()->addNamespace($this->srcFolder . '/Views', $this->viewNamespace);
  }

  /**
   * [Description for onBeforeRender]
   *
   * @return void
   * 
   */
  public function onBeforeRender(): void
  {
  }

  /**
   * [Description for hook]
   *
   * @param string $hook
   * 
   * @return void
   * 
   */
  public function hook(string $hook): void
  {
  }

  /**
   * [Description for getRootUrlSlug]
   *
   * @return string
   * 
   */
  public function getRootUrlSlug(): string
  {
    return $this->manifest['rootUrlSlug'] ?? '';
  }

  /**
   * [Description for getNotificationsCount]
   *
   * @return int
   * 
   */
  public function getNotificationsCount(): int
  {
    return 0;
  }

  /**
   * [Description for translate]
   *
   * @param string $string
   * @param array $vars
   * @param string $context
   * 
   * @return string
   * 
   */
  public function translate(string $string, array $vars = [], string $context = 'root'): string
  {
    return $this->translator()->translate($string, $vars, $this->fullName . '::' . $context);
  }

  /**
   * [Description for installTables]
   *
   * @param int $round
   * 
   * @return void
   * 
   */
  public function installTables(int $round): void
  {
    if ($round == 1) {
      // to be overriden
    }
  }

  /**
   * [Description for getAvailableControllerClasses]
   *
   * @return array
   * 
   */
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

  /**
   * [Description for getAvailableModelClasses]
   *
   * @return array
   * 
   */
  public function getAvailableModelClasses(): array
  {
    $modelClasses = [];

    $modelsFolder = $this->srcFolder . '/Models';
    if (is_dir($modelsFolder)) {
      $models = scandir($modelsFolder);
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

  /**
   * [Description for installDefaultPermissions]
   *
   * @return void
   * 
   */
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

    $mPermission = $this->getModel(\Hubleto\App\Community\Settings\Models\Permission::class);

    foreach ($permissions as $permission) {
      $mPermission->record->recordCreate([
        "permission" => $permission
      ]);
    }
  }

  /**
   * [Description for assignPermissionsToRoles]
   *
   * @return void
   * 
   */
  public function assignPermissionsToRoles(): void
  {

    /** @var \Hubleto\Framework\Models\UserRole */
    $mUserRole = $this->permissionsManager()->createUserRoleModel();

    /** @var \Hubleto\Framework\Models\RolePermission */
    $mRolePermission = $this->permissionsManager()->createRolePermissionModel();

    $userRoles = $mUserRole->record->get()->toArray();
    foreach ($userRoles as $role) {
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Table/Describe');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Form/Describe');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Get');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Delete');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/GetList');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Lookup');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Framework/Controllers/Api/Record/Save');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Erp/Core/Api/GetTableColumnsCustomize');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Erp/Core/Api/SaveTableColumnsCustomize');
      $mRolePermission->grantPermissionByString($role['id'], 'Hubleto/Erp/Core/Api/GetTemplateChartData');
    }

    $controllerClasses = $this->getAvailableControllerClasses();
    foreach ($controllerClasses as $controllerClass) {
      $cObj = $this->getController($controllerClass);
      foreach ($userRoles as $role) {
        $mRolePermission->grantPermissionByString($role['id'], $cObj->fullName);
      }
    }

  }

  /**
   * [Description for generateDemoData]
   *
   * @return void
   * 
   */
  public function generateDemoData(): void
  {
    // to be overriden
  }

  /**
   * [Description for renderSecondSidebar]
   *
   * @return string
   * 
   */
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
  /**
   * [Description for search]
   *
   * @param array $expressions
   * 
   * @return array
   * 
   */
  public function search(array $expressions): array
  {
    return [];
  }

  /**
   * [Description for addSetting]
   *
   * @param \Hubleto\Framework\Interfaces\AppInterface $app
   * @param array $setting
   * 
   * @return void
   * 
   */
  public function addSetting(\Hubleto\Framework\Interfaces\AppInterface $app, array $setting): void
  {
    $this->settings[] = [$app, $setting];
  }

  /**
   * [Description for getSettings]
   *
   * @return array
   * 
   */
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

  /**
   * [Description for getFullConfigPath]
   *
   * @param string $path
   * 
   * @return string
   * 
   */
  public function getFullConfigPath(string $path): string
  {
    return 'apps/' . $this->appManager()->getAppNamespaceForConfig($this->namespace) . '/' . $path;
  }

  /**
   * [Description for saveConfig]
   *
   * @param string $path
   * @param string $value
   * 
   * @return void
   * 
   */
  public function saveConfig(string $path, string $value = ''): void
  {
    $this->config()->save($this->getFullConfigPath($path), $value);
  }

  /**
   * [Description for saveConfigForUser]
   *
   * @param string $path
   * @param string $value
   * 
   * @return void
   * 
   */
  public function saveConfigForUser(string $path, string $value = ''): void
  {
    $this->config()->saveForUser($this->getFullConfigPath($path), $value);
  }

  /**
   * [Description for configAsString]
   *
   * @param string $path
   * @param string $defaultValue
   * 
   * @return string
   * 
   */
  public function configAsString(string $path, string $defaultValue = ''): string
  {
    return (string) $this->config()->get($this->getFullConfigPath($path), $defaultValue);
  }

  /**
   * [Description for configAsInteger]
   *
   * @param string $path
   * @param int $defaultValue
   * 
   * @return int
   * 
   */
  public function configAsInteger(string $path, int $defaultValue = 0): int
  {
    return (int) $this->config()->get($this->getFullConfigPath($path), $defaultValue);
  }

  /**
   * [Description for configAsFloat]
   *
   * @param string $path
   * @param float $defaultValue
   * 
   * @return float
   * 
   */
  public function configAsFloat(string $path, float $defaultValue = 0): float
  {
    return (float) $this->config()->get($this->getFullConfigPath($path), $defaultValue);
  }

  /**
   * [Description for configAsBool]
   *
   * @param string $path
   * @param bool $defaultValue
   * 
   * @return bool
   * 
   */
  public function configAsBool(string $path, bool $defaultValue = false): bool
  {
    return (bool) $this->config()->get($this->getFullConfigPath($path), $defaultValue);
  }

  /**
   * [Description for configAsArray]
   *
   * @param string $path
   * @param array $defaultValue
   * 
   * @return array
   * 
   */
  public function configAsArray(string $path, array $defaultValue = []): array
  {
    return (array) $this->config()->get($path, $defaultValue);
  }

  /**
   * [Description for setConfigAsString]
   *
   * @param string $path
   * @param string $value
   * 
   * @return void
   * 
   */
  public function setConfigAsString(string $path, string $value = ''): void
  {
    $this->config()->set($this->getFullConfigPath($path), $value);
  }

  /**
   * [Description for setConfigAsInteger]
   *
   * @param string $path
   * @param int $value
   * 
   * @return void
   * 
   */
  public function setConfigAsInteger(string $path, int $value = 0): void
  {
    $this->config()->set($this->getFullConfigPath($path), $value);
  }

  /**
   * [Description for setConfigAsFloat]
   *
   * @param string $path
   * @param float $value
   * 
   * @return void
   * 
   */
  public function setConfigAsFloat(string $path, float $value = 0): void
  {
    $this->config()->set($this->getFullConfigPath($path), $value);
  }

  /**
   * [Description for setConfigAsBool]
   *
   * @param string $path
   * @param bool $value
   * 
   * @return void
   * 
   */
  public function setConfigAsBool(string $path, bool $value = false): void
  {
    $this->config()->set($this->getFullConfigPath($path), $value);
  }

  /**
   * [Description for setConfigAsArray]
   *
   * @param string $path
   * @param array $value
   * 
   * @return void
   * 
   */
  public function setConfigAsArray(string $path, array $value = []): void
  {
    $this->config()->set($this->getFullConfigPath($path), $value);
  }

  /**
   * [Description for dangerouslyInjectDesktopHtmlContent]
   *
   * @param string $where
   * 
   * @return string
   * 
   */
  public function dangerouslyInjectDesktopHtmlContent(string $where): string
  {
    return '';
  }

  /**
   * [Description for addSearchSwitch]
   *
   * @param string $switch
   * @param string $name
   * 
   * @return void
   * 
   */
  public function addSearchSwitch(string $switch, string $name): void
  {
    $this->searchSwitches[$switch] = $name;
  }

  /**
   * [Description for canHandleSearchSwith]
   *
   * @param string $switch
   * 
   * @return bool
   * 
   */
  public function canHandleSearchSwith(string $switch): bool
  {
    return isset($this->searchSwitches[$switch]);
  }

  /**
   * [Description for collectExtendibles]
   *
   * @param string $extendibleName
   * 
   * @return array
   * 
   */
  public function collectExtendibles(string $extendibleName): array
  {
    $items = [];
    foreach ($this->appManager()->getEnabledApps() as $app) {
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
