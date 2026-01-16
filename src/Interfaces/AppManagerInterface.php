<?php declare(strict_types=1);

namespace Hubleto\Framework\Interfaces;

interface AppManagerInterface
{

  public function init(): void;
  public function sanitizeAppNamespace(string $appNamespace): string;
  public function validateAppNamespace(string $appNamespace): void;
  public function onBeforeRender(): void;
  public function getAppNamespaceForConfig(string $appNamespace): string;
  public function getAvailableApps(): array;
  public function getInstalledAppNamespaces(): array;
  public function createAppInstance(string $appNamespace): AppInterface;
  public function getEnabledApps(): array;
  public function getDisabledApps(): array;
  public function getInstalledApps(): array;
  public function getActivatedApp(): null|AppInterface;
  public function getApp(string $appNamespace): null|AppInterface;
  public function getCommunityApp(string $appShortName): null|AppInterface;
  public function isAppInstalled(string $appNamespace): bool;
  public function isAppEnabled(string $appNamespace): bool;
  public function installApp(int $round, string $appNamespace, array $appConfig = [], bool $forceInstall = false): bool;
  public function disableApp(string $appNamespace): void;
  public function enableApp(string $appNamespace): void;
  public function canAppDangerouslyInjectDesktopHtmlContent(string $appNamespace): bool;

}
