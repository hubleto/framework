<?php

namespace Hubleto\Framework;

class App
{
  public const DEFAULT_INSTALLATION_CONFIG = [
    'sidebarOrder' => 500,
  ];

  public const APP_TYPE_COMMUNITY = 1;
  public const APP_TYPE_PREMIUM = 2;
  public const APP_TYPE_EXTERNAL = 3;

  public \HubletoMain\Cli\Agent\Loader|null $cli;

  /**
  * @var array<string>
  */
  protected array $registeredModels = [];

  public array $manifest = [];

  public bool $enabled = false;
  public bool $canBeDisabled = true;

  public bool $permittedForAllUsers = false;

  public string $srcFolder = '';
  public string $viewNamespace = '';
  public string $namespace = '';
  public string $fullName = '';

  public string $translationContext = '';

  public bool $isActivated = false;
  public bool $hasCustomSettings = false;

  public static function canBeAdded(\Hubleto\Framework\Loader $main): bool
  {
    return true;
  }

  public function __construct(public \Hubleto\Framework\Loader $main)
  {
    $reflection = new \ReflectionClass($this);

    $this->cli = null;
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

    if (str_starts_with($this->namespace, 'HubletoApp\\Community')) {
      $this->manifest['appType'] = self::APP_TYPE_COMMUNITY;
    }
    if (str_starts_with($this->namespace, 'HubletoApp\\Premium')) {
      $this->manifest['appType'] = self::APP_TYPE_PREMIUM;
    }
    if (str_starts_with($this->namespace, 'HubletoApp\\External')) {
      $this->manifest['appType'] = self::APP_TYPE_EXTERNAL;
    }


    $this->validateManifest();

  }

  public function validateManifest()
  {
    $missing = [];
    if (empty($this->manifest['namespace'])) {
      $missing[] = 'namespace';
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
  }

  public function init(): void
  {
    $this->manifest['nameTranslated'] = $this->translate($this->manifest['name'], [], 'manifest');
    $this->manifest['highlightTranslated'] = $this->translate($this->manifest['highlight'], [], 'manifest');

    $this->main->addTwigViewNamespace($this->srcFolder . '/Views', $this->viewNamespace);
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

  public function setCli(\HubletoMain\Cli\Agent\Loader $cli): void
  {
    $this->cli = $cli;
  }

  public function getNotificationsCount(): int
  {
    return 0;
  }

  public function createTestInstance(string $test): \Hubleto\Framework\AppTest
  {
    $reflection = new \ReflectionClass($this);
    $testClass = $reflection->getNamespaceName() . '\\Tests\\' . $test;
    return new $testClass($this, $this->cli); // @phpstan-ignore-line
  }

  public function test(string $test): void
  {
    ($this->createTestInstance($test))->run();
  }

  /** @return array<string> */
  public function getAllTests(): array
  {
    $tests = [];
    $testFiles = (array) @scandir($this->srcFolder . '/Tests');
    foreach ($testFiles as $testFile) {
      if (substr($testFile, -4) == '.php') {
        $tests[] = substr($testFile, 0, -4);
      }
    }

    return $tests;
  }

  public static function getDictionaryFilename(string $language): string
  {
    if (strlen($language) == 2) {
      $appClass = get_called_class();
      $reflection = new \ReflectionClass(get_called_class());
      $srcFolder = pathinfo((string) $reflection->getFilename(), PATHINFO_DIRNAME);
      return $srcFolder . '/Lang/' . $language . '.json';
    } else {
      return '';
    }
  }

  /**
  * @return array|array<string, array<string, string>>
  */
  public static function loadDictionary(string $language): array
  {
    $dict = [];
    $dictFilename = static::getDictionaryFilename($language);
    if (is_file($dictFilename)) {
      $dict = (array) @json_decode((string) file_get_contents($dictFilename), true);
    }
    return $dict;
  }

  /**
  * @return array|array<string, array<string, string>>
  */
  public static function addToDictionary(string $language, string $contextInner, string $string): void
  {
    $dictFilename = static::getDictionaryFilename($language);

    $dict = static::loadDictionary($language);

    $main = \Hubleto\Framework\Loader::getGlobalApp();

    if ($main->config->getAsBool('autoTranslate')) {
      $tr = new \Stichoza\GoogleTranslate\GoogleTranslate();
      $tr->setSource('en'); // Translate from
      $tr->setTarget($language); // Translate to
      $dict[$contextInner][$string] = $tr->translate($string);
    } else {
      $dict[$contextInner][$string] = '';
    }

    @file_put_contents($dictFilename, json_encode($dict, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }

  public function translate(string $string, array $vars = [], string $context = 'root'): string
  {
    return $this->main->translate($string, $vars, $this->fullName . '::' . $context);
  }

  public function registerModel(string $model): void
  {
    if (!in_array($model, $this->registeredModels)) {
      $this->registeredModels[] = $model;
    }
  }

  /**
  * @return array<string>
  */
  public function getRegisteredModels(): array
  {
    return $this->registeredModels;
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
            $mObj = $this->main->di->create($mClass::class);
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
    $permissions = [
      'Api/Table/Describe',
      'Api/Form/Describe',
      'Api/Record/Get',
      'Api/Record/GetList',
      'Api/Record/Lookup',
      'Api/Record/Save',
    ];

    $controllersFolder = $this->srcFolder . '/Controllers';
    if (is_dir($controllersFolder)) {
      $controllers = Helper::scanDirRecursively($controllersFolder);
      foreach ($controllers as $controller) {
        $cClass = $this->namespace . '/Controllers/' . $controller;
        $cClass = str_replace('/', '\\', $cClass);
        $cClass = str_replace('.php', '', $cClass);
        if (class_exists($cClass)) {
          $cObj = new $cClass($this->main);
          $permissions[] = $cObj->permission;
        }
      }
    }

    $modelsFolder = $this->srcFolder . '/Models';
    if (is_dir($modelsFolder)) {
      $models = Helper::scanDirRecursively($modelsFolder);
      foreach ($models as $model) {
        $mClass = $this->namespace . '/Models/' . $model;
        $mClass = str_replace('/', '\\', $mClass);
        $mClass = str_replace('.php', '', $mClass);
        if (class_exists($mClass)) {
          try {
            $mObj = $this->main->di->create($mClass::class);
            $permissions[] = $mObj->fullName . ':Create';
            $permissions[] = $mObj->fullName . ':Read';
            $permissions[] = $mObj->fullName . ':Update';
            $permissions[] = $mObj->fullName . ':Delete';
          } catch (\Throwable) {
          }
        }
      }
    }

    $mPermission = $this->main->di->create(\HubletoApp\Community\Settings\Models\Permission::class);

    foreach ($permissions as $permission) {
      $mPermission->record->recordCreate([
        "permission" => $permission
      ]);
    }
  }

  public function assignPermissionsToRoles(): void
  {
    $mUserRole = $this->main->di->create(\HubletoApp\Community\Settings\Models\UserRole::class);
    $mRolePermission = $this->main->di->create(\HubletoApp\Community\Settings\Models\RolePermission::class);

    $userRoles = $mUserRole->record->get()->toArray();
    foreach ($userRoles as $role) {
      $mRolePermission->grantPermissionByString($role['id'], 'Api/Table/Describe');
      $mRolePermission->grantPermissionByString($role['id'], 'Api/Form/Describe');
      $mRolePermission->grantPermissionByString($role['id'], 'Api/Record/Get');
      $mRolePermission->grantPermissionByString($role['id'], 'Api/Record/Delete');
      $mRolePermission->grantPermissionByString($role['id'], 'Api/Record/GetList');
      $mRolePermission->grantPermissionByString($role['id'], 'Api/Record/Lookup');
      $mRolePermission->grantPermissionByString($role['id'], 'Api/Record/Save');
      $mRolePermission->grantPermissionByString($role['id'], 'HubletoMain/Core/Api/GetTableColumnsCustomize');
      $mRolePermission->grantPermissionByString($role['id'], 'HubletoMain/Core/Api/SaveTableColumnsCustomize');
      $mRolePermission->grantPermissionByString($role['id'], 'HubletoMain/Core/Api/GetTemplateChartData');
    }

    $controllerClasses = $this->getAvailableControllerClasses();
    foreach ($controllerClasses as $controllerClass) {
      $cObj = new $controllerClass($this->main);
      foreach ($userRoles as $role) {
        $mRolePermission->grantPermissionByString($role['id'], $cObj->permission);
      }
    }

    // $modelClasses = $this->getAvailableModelClasses();
    // foreach ($modelClasses as $modelClass) {
    //   $mObj = $this->main->di->create($modelClass::class);
    //   foreach ($mObj->rolePermissions as $idRole => $rolePermissions) {
    //     $mRolePermission->grantPermissionsForModel($idRole, $mObj->fullName, $rolePermissions);
    //   }
    // }
  }

  public function generateDemoData(): void
  {
    // to be overriden
  }

  public function renderSecondSidebar(): string
  {
    return '';
  }

  public function getFullConfigPath(string $path): string
  {
    return 'apps/' . $this->main->apps->getAppNamespaceForConfig($this->namespace) . '/' . $path;
  }

  public function saveConfig(string $path, string $value = ''): void
  {
    $this->main->config->save($this->getFullConfigPath($path), $value);
  }

  public function saveConfigForUser(string $path, string $value = ''): void
  {
    $this->main->config->saveForUser($this->getFullConfigPath($path), $value);
  }


  public function configAsString(string $path, string $defaultValue = ''): string
  {
    return (string) $this->main->config->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsInteger(string $path, int $defaultValue = 0): int
  {
    return (int) $this->main->config->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsFloat(string $path, float $defaultValue = 0): float
  {
    return (float) $this->main->config->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsBool(string $path, bool $defaultValue = false): bool
  {
    return (bool) $this->main->config->get($this->getFullConfigPath($path), $defaultValue);
  }

  public function configAsArray(string $path, array $defaultValue = []): array
  {
    return (array) $this->main->config->get($path, $defaultValue);
  }

  public function setConfigAsString(string $path, string $value = ''): void
  {
    $this->main->config->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsInteger(string $path, int $value = 0): void
  {
    $this->main->config->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsFloat(string $path, float $value = 0): void
  {
    $this->main->config->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsBool(string $path, bool $value = false): void
  {
    $this->main->config->set($this->getFullConfigPath($path), $value);
  }

  public function setConfigAsArray(string $path, array $value = []): void
  {
    $this->main->config->set($this->getFullConfigPath($path), $value);
  }



  public function dangerouslyInjectDesktopHtmlContent(string $where): string
  {
    return '';
  }

}
