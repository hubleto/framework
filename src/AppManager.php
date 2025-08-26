<?php declare(strict_types=1);

namespace Hubleto\Framework;

/**
 * @property \HubletoMain\Loader $main
 */
class AppManager extends Core implements Interfaces\AppManagerInterface
{
  public Interfaces\AppInterface $activatedApp;

  /** @var array<Interfaces\AppInterface> */
  public array $enabledApps = [];

  /** @var array<Interfaces\AppInterface> */
  public array $disabledApps = [];

  /** @var array<string> */
  public array $registeredAppNamespaces = [];

  /**
   * [Description for init]
   *
   * @return void
   * 
   */
  public function init(): void
  {
    foreach ($this->getInstalledAppNamespaces() as $appNamespace => $appConfig) {
      $appNamespace = (string) $appNamespace;
      $appClass = $appNamespace . '\\Loader';
      if (is_array($appConfig)) {
        try {
          if ($appConfig['enabled'] ?? false) {
            $this->enabledApps[$appNamespace] = $this->createAppInstance($appNamespace);
            $this->enabledApps[$appNamespace]->enabled = true;
          } else {
            $this->disabledApps[$appNamespace] = $this->createAppInstance($appNamespace);
          }
        } catch (\Throwable $e) {
          throw new \Exception("Failed to initialize app {$appNamespace}." . $e->getMessage());
          // do nothing, if app cannot be instantiated
        }
      }
    }

    $apps = $this->getEnabledApps();
    array_walk($apps, function ($app) {
      if (
        $this->getEnv()->requestedUri == $app->manifest['rootUrlSlug']
        || str_starts_with($this->getEnv()->requestedUri, $app->manifest['rootUrlSlug'] . '/')
      ) {
        $app->isActivated = true;
        $this->activatedApp = $app;
      }

      $app->init();
    });

  }

  /**
   * [Description for sanitizeAppNamespace]
   *
   * @param string $appNamespace
   * 
   * @return string
   * 
   */
  public function sanitizeAppNamespace(string $appNamespace): string
  {
    $appNamespace = trim($appNamespace, '\\');
    if (strpos($appNamespace, '\\') === false) {
      $appNamespace = 'HubletoApp\\Custom\\' . $appNamespace;
    }

    if (str_ends_with($appNamespace, '\\Loader')) {
      $appNamespace = substr($appNamespace, 0, -7);
    }

    $this->validateAppNamespace($appNamespace);
    return $appNamespace;
  }

  /**
   * [Description for validateAppNamespace]
   *
   * @param string $appNamespace
   * 
   * @return void
   * 
   */
  public function validateAppNamespace(string $appNamespace): void
  {
    $appNamespace = trim($appNamespace, '\\');
    $appNamespaceParts = explode('\\', $appNamespace);

    if ($appNamespaceParts[0] != 'HubletoApp') {
      throw new \Exception('Application namespace must start with \'HubletoApp\'. See https://developer.hubleto.com/apps for more details.');
    }

    switch ($appNamespaceParts[1]) {
      case 'Community':
        if (count($appNamespaceParts) != 3) {
          throw new \Exception('Community app namespace must have exactly 3 parts');
        }
        break;
      case 'Premium':
        if (count($appNamespaceParts) != 3) {
          throw new \Exception('Premium app namespace must have exactly 3 parts');
        }
        break;
      case 'External':
        if (count($appNamespaceParts) != 4) {
          throw new \Exception('External app namespace must have exactly 4 parts');
        }
        break;
      case 'Custom':
        if (count($appNamespaceParts) != 3) {
          throw new \Exception('Custom app namespace must have exactly 3 parts');
        }
        break;
      default:
        throw new \Exception('Only following types of apps are available: Community, Premium, External or Custom.');
        break;
    }

  }

  /**
   * [Description for onBeforeRender]
   *
   * @return void
   * 
   */
  public function onBeforeRender(): void
  {
    $apps = $this->getEnabledApps();
    array_walk($apps, function ($app) { $app->onBeforeRender(); });
  }

  /**
   * [Description for getAppNamespaceForConfig]
   *
   * @param string $appNamespace
   * 
   * @return string
   * 
   */
  public function getAppNamespaceForConfig(string $appNamespace): string
  {
    return trim($appNamespace, '\\');
  }

  /**
   * [Description for getAvailableApps]
   *
   * @return array
   * 
   */
  public function getAvailableApps(): array
  {
    $appNamespaces = [];

    // community apps
    $communityRepoFolder = $this->getEnv()->srcFolder . '/../../apps/src';
    if (is_dir($communityRepoFolder)) {
      foreach (scandir($communityRepoFolder) as $folder) {
        $manifestFile = $communityRepoFolder . '/' . $folder . '/manifest.yaml';
        if (@is_file($manifestFile)) {
          $manifestFileContent = file_get_contents($manifestFile);
          $manifest = (array) \Symfony\Component\Yaml\Yaml::parse((string) $manifestFileContent);
          $appNamespaces['HubletoApp\\Community\\' . $folder] = $manifest;
        }
      }
    }

    // appRepositories are supposed to be composer's 'vendor' folders
    // Each repository is scanned, first for the vendor name ($vendorFolder), then for
    // the app name ($appFolder).
    // The $appFolder represents the HubletoApp only if there is src/manifest.yaml file.
    $appRepositories = $this->getConfig()->getAsArray('appRepositories');
    if (count($appRepositories) == 0) {
      $appRepositories = [
        $this->getEnv()->projectFolder . '/vendor'
      ];
    }

    foreach ($appRepositories as $repoFolder) {
      if (!empty($repoFolder) && is_dir($repoFolder)) {
        foreach (scandir($repoFolder) as $vendorFolder) {
          if (in_array($vendorFolder, ['.', '..', 'hubleto'])) continue;
          if (!is_dir($repoFolder . '/' . $vendorFolder)) continue;
          foreach (scandir($repoFolder . '/' . $vendorFolder) as $appFolder) {
            $manifestFile = $repoFolder . '/' . $vendorFolder . '/' . $appFolder . '/src/manifest.yaml';
            if (@is_file($manifestFile)) {
              $manifestFileContent = file_get_contents($manifestFile);
              $manifest = (array) \Symfony\Component\Yaml\Yaml::parse((string) $manifestFileContent);
              $appNamespaces[$manifest['namespace']] = $manifest;
            }
          }
        }
      }
    }

    return $appNamespaces;
  }

  /**
   * [Description for getInstalledAppNamespaces]
   *
   * @return array
   * 
   */
  public function getInstalledAppNamespaces(): array
  {
    $tmp = $this->getConfig()->getAsArray('apps');
    ksort($tmp);

    $appNamespaces = [];
    foreach ($tmp as $key => $value) {
      $appNamespaces[str_replace('-', '\\', $key)] = $value;
    }

    return $appNamespaces;
  }

  /**
   * [Description for createAppInstance]
   *
   * @param string $appNamespace
   * 
   * @return Interfaces\AppInterface
   * 
   */
  public function createAppInstance(string $appNamespace): Interfaces\AppInterface
  {
     if (!str_ends_with($appNamespace, '\Loader')) $appNamespace = $appNamespace . '\Loader';
    return $this->getService($appNamespace);
  }

  /**
  * @return array<Interfaces\AppInterface>
  */
  public function getEnabledApps(): array
  {
    return $this->enabledApps;
  }

  /**
  * @return array<Interfaces\AppInterface>
  */
  public function getDisabledApps(): array
  {
    return $this->disabledApps;
  }

  /**
  * @return array<Interfaces\AppInterface>
  */
  public function getInstalledApps(): array
  {
    return array_merge($this->enabledApps, $this->disabledApps);
  }

  /**
   * [Description for getActivatedApp]
   *
   * @return null|Interfaces\AppInterface
   * 
   */
  public function getActivatedApp(): null|Interfaces\AppInterface
  {
    $apps = $this->getEnabledApps();
    foreach ($apps as $app) {
      if (str_starts_with($this->getEnv()->requestedUri, $app->getRootUrlSlug())) {
        return $app;
      }
    }
    return null;
  }

  /**
   * [Description for getApp]
   *
   * @param string $appNamespace
   * 
   * @return null|Interfaces\AppInterface
   * 
   */
  public function getApp(string $appNamespace): null|Interfaces\AppInterface
  {
    return $this->enabledApps[str_replace('\\Loader', '', $appNamespace)] ?? null;
  }

  /**
   * [Description for isAppInstalled]
   *
   * @param string $appNamespace
   * 
   * @return bool
   * 
   */
  public function isAppInstalled(string $appNamespace): bool
  {
    $apps = $this->getInstalledAppNamespaces();
    return isset($apps[$appNamespace]) && is_array($apps[$appNamespace]) && isset($apps[$appNamespace]['installedOn']);
  }

  /**
   * [Description for isAppEnabled]
   *
   * @param string $appNamespace
   * 
   * @return bool
   * 
   */
  public function isAppEnabled(string $appNamespace): bool
  {
    $apps = $this->getEnabledApps();
    return isset($apps[$appNamespace]);
  }

  // /**
  //  * [Description for community]
  //  *
  //  * @param string $appName
  //  * 
  //  * @return null|Interfaces\AppInterface
  //  * 
  //  */
  // public function community(string $appName): null|Interfaces\AppInterface
  // {
  //   return $this->getApp('HubletoApp\\Community\\' . $appName);
  // }

  // /**
  //  * [Description for custom]
  //  *
  //  * @param string $appName
  //  * 
  //  * @return null|Interfaces\AppInterface
  //  * 
  //  */
  // public function custom(string $appName): null|Interfaces\AppInterface
  // {
  //   return $this->getApp('HubletoApp\\Custom\\' . $appName);
  // }

  /** @param array<string, mixed> $appConfig */
  public function installApp(int $round, string $appNamespace, array $appConfig = [], bool $forceInstall = false): bool
  {
    $appNamespace = $this->sanitizeAppNamespace($appNamespace);

    \Hubleto\Terminal::cyan("    -> Installing {$appNamespace}, round {$round}.\n");

    if ($this->isAppInstalled($appNamespace) && !$forceInstall) {
      throw new \Exception("{$appNamespace} already installed. Set forceInstall to true if you want to reinstall.");
    }

    if (!class_exists($appNamespace . '\Loader')) {
      throw new \Exception("{$appNamespace} does not exist.");
    }

    $app = $this->createAppInstance($appNamespace);
    if (!file_exists($app->srcFolder . '/manifest.yaml')) {
      throw new \Exception("{$appNamespace} does not provide manifest.yaml file.");
    }

    $manifestFile = (string) file_get_contents($app->srcFolder . '/manifest.yaml');
    $manifest = (array) \Symfony\Component\Yaml\Yaml::parse($manifestFile);
    $dependencies = (array) ($manifest['requires'] ?? []);

    foreach ($dependencies as $dependencyAppNamespace) {
      $dependencyAppNamespace = (string) $dependencyAppNamespace;
      if (!$this->isAppInstalled($dependencyAppNamespace)) {
        \Hubleto\Terminal::cyan("    -> Installing dependency {$dependencyAppNamespace}.\n");
        $this->installApp($round, $dependencyAppNamespace, [], $forceInstall);
      }
    }

    $app->installTables($round);

    if ($round == 1) {
      $appConfig = array_merge($app::DEFAULT_INSTALLATION_CONFIG, $appConfig);

      $appNameForConfig = $this->getAppNamespaceForConfig($appNamespace);

      if (!in_array($appNamespace, $this->getInstalledAppNamespaces())) {
        $this->getConfig()->set('apps/' . $appNameForConfig . "/installedOn", date('Y-m-d H:i:s'));
        $this->getConfig()->set('apps/' . $appNameForConfig . "/enabled", true);
        $this->getConfig()->save('apps/' . $appNameForConfig . "/installedOn", date('Y-m-d H:i:s'));
        $this->getConfig()->save('apps/' . $appNameForConfig . "/enabled", '1');
      }

      foreach ($appConfig as $cPath => $cValue) {
        $this->getConfig()->set('apps/' . $appNameForConfig . "/" . $cPath, (string) $cValue);
        $this->getConfig()->save('apps/' . $appNameForConfig . "/" . $cPath, (string) $cValue);
      }
    }

    if ($round == 3) {
      $app->installDefaultPermissions();
      $app->assignPermissionsToRoles();
    }

    return true;
  }

  public function disableApp(string $appNamespace): void
  {
    $this->getConfig()->save('apps/' . $this->getAppNamespaceForConfig($appNamespace) . '/enabled', '0');
  }

  public function enableApp(string $appNamespace): void
  {
    $this->getConfig()->save('apps/' . $this->getAppNamespaceForConfig($appNamespace) . '/enabled', '1');
  }

  public function createApp(string $appNamespace, string $appSrcFolder): void
  {
    if (empty($appSrcFolder)) {
      throw new \Exception('App folder for \'' . $appNamespace . '\' not configured.');
    }
    if (!is_dir($appSrcFolder)) {
      throw new \Exception('App folder for \'' . $appNamespace . '\' is not a folder.');
    }

    $appNamespace = trim($appNamespace, '\\');
    $appNamespaceParts = explode('\\', $appNamespace);
    $appName = $appNamespaceParts[count($appNamespaceParts) - 1];
    $appType = strtolower($appNamespaceParts[1]);

    $tplVars = [
      'appNamespace' => $appNamespace,
      'appType' => $appType,
      'appName' => $appName,
      'appRootUrlSlug' => Helper::str2url($appName),
      'appViewNamespace' => str_replace('\\', ':', $appNamespace),
      'appNamespaceForwardSlash' => str_replace('\\', '/', $appNamespace),
      'now' => date('Y-m-d H:i:s'),
    ];

    $tplFolder = __DIR__ . '/../cli/Templates/app';

    $this->getRenderer()->addNamespace($tplFolder, 'appTemplate');

    if (!is_dir($appSrcFolder . '/Controllers')) {
      mkdir($appSrcFolder . '/Controllers');
    }
    if (!is_dir($appSrcFolder . '/Views')) {
      mkdir($appSrcFolder . '/Views');
    }
    if (!is_dir($appSrcFolder . '/Extendibles')) {
      mkdir($appSrcFolder . '/Extendibles');
    }

    file_put_contents($appSrcFolder . '/Loader.php', $this->getRenderer()->renderView('@appTemplate/Loader.php.twig', $tplVars));
    file_put_contents($appSrcFolder . '/Loader.tsx', $this->getRenderer()->renderView('@appTemplate/Loader.tsx.twig', $tplVars));
    file_put_contents($appSrcFolder . '/Calendar.php', $this->getRenderer()->renderView('@appTemplate/Calendar.php.twig', $tplVars));
    file_put_contents($appSrcFolder . '/manifest.yaml', $this->getRenderer()->renderView('@appTemplate/manifest.yaml.twig', $tplVars));
    file_put_contents($appSrcFolder . '/Controllers/Home.php', $this->getRenderer()->renderView('@appTemplate/Controllers/Home.php.twig', $tplVars));
    file_put_contents($appSrcFolder . '/Controllers/Settings.php', $this->getRenderer()->renderView('@appTemplate/Controllers/Settings.php.twig', $tplVars));
    file_put_contents($appSrcFolder . '/Views/Home.twig', $this->getRenderer()->renderView('@appTemplate/Views/Home.twig.twig', $tplVars));
    file_put_contents($appSrcFolder . '/Views/Settings.twig', $this->getRenderer()->renderView('@appTemplate/Views/Settings.twig.twig', $tplVars));
    file_put_contents($appSrcFolder . '/Extendibles/AppMenu.php', $this->getRenderer()->renderView('@appTemplate/Extendibles/AppMenu.php.twig', $tplVars));
  }

  public function canAppDangerouslyInjectDesktopHtmlContent(string $appNamespace): bool
  {
    $safeApps = [
      'HubletoApp\\Community\\Cloud',
    ];

    return in_array($appNamespace, $safeApps);
  }

}
