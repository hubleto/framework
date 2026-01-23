<?php

namespace Hubleto\Framework\Interfaces;

interface AppInterface
{
  public const DEFAULT_INSTALLATION_CONFIG = [
    'sidebarOrder' => 500,
  ];

  public const APP_TYPE_COMMUNITY = 'community';
  public const APP_TYPE_ENTERPRISE = 'enterprise';
  public const APP_TYPE_EXTERNAL = 'external';

  public function validateManifest();
  public function init(): void;
  public function onBeforeRender(): void;
  public function getRootUrlSlug(): string;
  public function installTables(int $round): void;
  public function getAvailableControllerClasses(): array;
  public function getAvailableModelClasses(): array;
  public function installDefaultPermissions(): void;
  public function assignPermissionsToRoles(): void;
  public function generateDemoData(): void;
  public function renderSecondSidebar(): string;
  public function search(array $expressions): array;
  public function addSetting(AppInterface $app, array $setting): void;
  public function getSettings(): array;
  public function getFullConfigPath(string $path): string;
  public function saveConfig(string $path, string $value = ''): void;
  public function saveConfigForUser(string $path, string $value = ''): void;
  public function configAsString(string $path, string $defaultValue = ''): string;
  public function configAsInteger(string $path, int $defaultValue = 0): int;
  public function configAsFloat(string $path, float $defaultValue = 0): float;
  public function configAsBool(string $path, bool $defaultValue = false): bool;
  public function configAsArray(string $path, array $defaultValue = []): array;
  public function setConfigAsString(string $path, string $value = ''): void;
  public function setConfigAsInteger(string $path, int $value = 0): void;
  public function setConfigAsFloat(string $path, float $value = 0): void;
  public function setConfigAsBool(string $path, bool $value = false): void;
  public function setConfigAsArray(string $path, array $value = []): void;
  public function dangerouslyInjectDesktopHtmlContent(string $where): string;
  public function collectExtendibles(string $extendibleName): array;
  public function addSearchSwitch(string $switch, string $name): void;
  public function canHandleSearchSwith(string $switch): bool;

}
