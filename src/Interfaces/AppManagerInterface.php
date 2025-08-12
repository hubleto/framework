<?php declare(strict_types=1);

namespace Hubleto\Framework\Interfaces;

interface AppManagerInterface
{
  public \Hubleto\Framework\App $activatedApp { get; set; }

  /** @var array<\Hubleto\Framework\App> */
  public array $apps { get; set; }

  /** @var array<\Hubleto\Framework\App> */
  public array $disabledApps { get; set; }

  /** @var array<string> */
  public array $registeredAppNamespaces { get; set; }

  public function __construct(\Hubleto\Framework\Loader $main);
  public function init(): void;
  public function sanitizeAppNamespace(string $appNamespace): string;
  public function validateAppNamespace(string $appNamespace): void;
  public function onBeforeRender(): void;
  public function getAppNamespaceForConfig(string $appNamespace): string;
  public function getAvailableApps(): array;
  public function getInstalledAppNamespaces(): array;
  public function createAppInstance(string $appNamespace): \Hubleto\Framework\App;
  public function getEnabledApps(): array;
  public function getDisabledApps(): array;
  public function getInstalledApps(): array;
  public function getActivatedApp(): \Hubleto\Framework\App|null;
  public function getAppInstance(string $appNamespace): null|\Hubleto\Framework\App;
  public function isAppInstalled(string $appNamespace): bool;
  public function community(string $appName): null|\Hubleto\Framework\App;
  public function custom(string $appName): null|\Hubleto\Framework\App;
  public function installApp(int $round, string $appNamespace, array $appConfig = [], bool $forceInstall = false): bool;
  public function disableApp(string $appNamespace): void;
  public function enableApp(string $appNamespace): void;
  public function testApp(string $appNamespace, string $test): void;
  public function createApp(string $appNamespace, string $appSrcFolder): void;
  public function canAppDangerouslyInjectDesktopHtmlContent(string $appNamespace): bool;

}
