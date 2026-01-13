<?php declare(strict_types=1);

namespace Hubleto\Framework;

/**
 * Default manager of Hubleto apps used in the Hubleto project.
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
          $this->enabledApps[$appNamespace] = $this->createAppInstance($appNamespace);

          if ($appConfig['enabled'] ?? false) {
            $this->enabledApps[$appNamespace]->enabled = true;
          }
        } catch (\Throwable $e) {
          // do nothing, if app cannot be instantiated
        }
      }
    }

    $apps = $this->getEnabledApps();
    array_walk($apps, function ($app) {
      if (
        $this->env()->requestedUri == $app->manifest['rootUrlSlug']
        || str_starts_with($this->env()->requestedUri, $app->manifest['rootUrlSlug'] . '/')
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
      $appNamespace = 'Hubleto\\App\\Custom\\' . $appNamespace;
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

    if (
      $appNamespaceParts[0] != 'Hubleto'
      && $appNamespaceParts[1] != 'App'
    ) {
      throw new \Exception('Application namespace must start with \'Hubleto\\App\'. See https://developer.hubleto.com/apps for more details.');
    }
    if ($appNamespaceParts[2] == 'External') {
      if (count($appNamespaceParts) != 5) {
        throw new \Exception('External app namespace (' . $appNamespace . ') must have exactly 5 parts and has ' . count($appNamespaceParts));
      }
    } else {
      if (count($appNamespaceParts) != 4) {
        throw new \Exception('App namespace (' . $appNamespace . ') must have exactly 4 parts and has ' . count($appNamespaceParts));
      }
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
   * [Description for loadAppInfoFromPath]
   *
   * @param string $path
   * 
   * @return array|bool
   * 
   */
  public function loadAppManifestFromPath(string $path): array|bool
  {
    $manifestFile = $path . '/manifest.yaml';
    $loaderFile = $path . '/Loader.php';
    if (@is_file($manifestFile) && @is_file($loaderFile)) {
      $manifestFileContent = file_get_contents($manifestFile);
      $manifest = (array) \Symfony\Component\Yaml\Yaml::parse((string) $manifestFileContent);
      if (
        str_starts_with($manifest['namespace'], 'Hubleto\\App\\')
        && class_exists($manifest['namespace'] . '\\Loader')
      ) {
        return $manifest;
      } else {
        return false;
      }
    } else {
      return false;
    }
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


    $packages = \Composer\InstalledVersions::getInstalledPackages();
    foreach ($packages as $package) {
      $path = \Composer\InstalledVersions::getInstallPath($package);

      if (is_dir($path . '/src')) {
        // this package is a single app
        $manifest = $this->loadAppManifestFromPath($path . '/src');
        if (is_array($manifest)) {
          $appNamespaces[$manifest['namespace']] = $manifest;
        }
      }

      if (is_dir($path . '/apps')) {
        // this package is a repository of multiple app
        foreach (scandir($path . '/apps') as $app) {
          $manifest = $this->loadAppManifestFromPath($path . '/apps/' . $app);
          if (is_array($manifest)) {
            $appNamespaces[$manifest['namespace']] = $manifest;
          }
        }
      }
    }

    // // community apps
    // $communityRepoFolder = $this->env()->srcFolder . '/../apps';
    // if (is_dir($communityRepoFolder)) {
    //   foreach (scandir($communityRepoFolder) as $folder) {
    //     $manifestFile = $communityRepoFolder . '/' . $folder . '/manifest.yaml';
    //     if (@is_file($manifestFile)) {
    //       $manifestFileContent = file_get_contents($manifestFile);
    //       $manifest = (array) \Symfony\Component\Yaml\Yaml::parse((string) $manifestFileContent);
    //       $appNamespaces['Hubleto\\App\\Community\\' . $folder] = $manifest;
    //     }
    //   }
    // }

    // // appRepositories are supposed to be composer's 'vendor' folders
    // // Each repository is scanned, first for the vendor name ($vendorFolder), then for
    // // the app name ($appFolder).
    // // The $appFolder represents the Hubleto\App only if there is src/manifest.yaml and src/Loader.php file.
    // $appRepositories = $this->config()->getAsArray('appRepositories');
    // $appRepositories[] = $this->env()->projectFolder . '/vendor';

    // foreach ($appRepositories as $repoFolder) {
    //   if (!empty($repoFolder) && is_dir($repoFolder)) {
    //     foreach (scandir($repoFolder) as $vendorFolder) {
    //       if (in_array($vendorFolder, ['.', '..', 'hubleto'])) continue;
    //       if (!is_dir($repoFolder . '/' . $vendorFolder)) continue;
    //       foreach (scandir($repoFolder . '/' . $vendorFolder) as $appFolder) {
    //         $manifestFile = $repoFolder . '/' . $vendorFolder . '/' . $appFolder . '/src/manifest.yaml';
    //         $loaderFile = $repoFolder . '/' . $vendorFolder . '/' . $appFolder . '/src/Loader.php';
    //         if (@is_file($manifestFile)) {
    //           $manifestFileContent = file_get_contents($manifestFile);
    //           $manifest = (array) \Symfony\Component\Yaml\Yaml::parse((string) $manifestFileContent);
    //           $appNamespaces[$manifest['namespace']] = $manifest;
    //         }
    //       }
    //     }
    //   }
    // }

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
    $tmp = $this->config()->getAsArray('apps');
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
      if (str_starts_with($this->env()->requestedUri, $app->getRootUrlSlug())) {
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
    $appNamespace = str_replace('/', '\\', $appNamespace);
    $appNamespace = str_replace('\\Loader', '', $appNamespace);
    return $this->enabledApps[$appNamespace] ?? null;
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

  /** @param array<string, mixed> $appConfig */
  public function installApp(int $round, string $appNamespace, array $appConfig = [], bool $forceInstall = false): bool
  {
    $appNamespace = $this->sanitizeAppNamespace($appNamespace);

    $this->terminal()->cyan("    -> Installing {$appNamespace}, round {$round}.\n");

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
        $this->terminal()->cyan("    -> Installing dependency {$dependencyAppNamespace}.\n");
        $this->installApp($round, $dependencyAppNamespace, [], $forceInstall);
      }
    }

    $app->installTables($round);

    if ($round == 1) {
      $appConfig = array_merge($app::DEFAULT_INSTALLATION_CONFIG, $appConfig);

      $appNameForConfig = $this->getAppNamespaceForConfig($appNamespace);

      if (!in_array($appNamespace, $this->getInstalledAppNamespaces())) {
        $this->config()->set('apps/' . $appNameForConfig . "/installedOn", date('Y-m-d H:i:s'));
        $this->config()->set('apps/' . $appNameForConfig . "/enabled", true);
        $this->config()->save('apps/' . $appNameForConfig . "/installedOn", date('Y-m-d H:i:s'));
        $this->config()->save('apps/' . $appNameForConfig . "/enabled", '1');
      }

      foreach ($appConfig as $cPath => $cValue) {
        $this->config()->set('apps/' . $appNameForConfig . "/" . $cPath, (string) $cValue);
        $this->config()->save('apps/' . $appNameForConfig . "/" . $cPath, (string) $cValue);
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
    $this->config()->save('apps/' . $this->getAppNamespaceForConfig($appNamespace) . '/enabled', '0');
  }

  public function enableApp(string $appNamespace): void
  {
    $this->config()->save('apps/' . $this->getAppNamespaceForConfig($appNamespace) . '/enabled', '1');
  }

  public function canAppDangerouslyInjectDesktopHtmlContent(string $appNamespace): bool
  {
    $safeApps = [
      'Hubleto\\App\\Community\\Cloud',
    ];

    return in_array($appNamespace, $safeApps);
  }

}
