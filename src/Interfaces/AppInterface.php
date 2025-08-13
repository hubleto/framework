<?php

namespace Hubleto\Framework\Interfaces;

interface AppInterface
{
  public const DEFAULT_INSTALLATION_CONFIG = [
    'sidebarOrder' => 500,
  ];

  public const APP_TYPE_COMMUNITY = 'community';
  public const APP_TYPE_PREMIUM = 'premium';
  public const APP_TYPE_EXTERNAL = 'external';

  public array $manifest { get; set; }

  public bool $enabled { get; set; }
  public bool $canBeDisabled { get; set; }

  public bool $permittedForAllUsers { get; set; }

  public string $srcFolder { get; set; }
  public string $viewNamespace { get; set; }
  public string $namespace { get; set; }
  public string $fullName { get; set; }

  public string $translationContext { get; set; }

  public bool $isActivated { get; set; }
  public bool $hasCustomSettings { get; set; }

  public AppMenuManagerInterface $appMenu { get; set; }

  /** @var array<int, array<\Hubleto\Framework\App, array>> */
  public array $settings { get; set; }

  public static function canBeAdded(\Hubleto\Framework\Loader $main): bool;
  public function validateManifest();
  public function init(): void;
  public function onBeforeRender(): void;
  public function hook(string $hook): void;
  public function getRootUrlSlug(): string;
  public function getNotificationsCount(): int;
  public function createTestInstance(string $test): \Hubleto\Framework\AppTest;
  public function test(string $test): void;
  public function getAllTests(): array;
  public static function getDictionaryFilename(string $language): string;
  public static function loadDictionary(string $language): array;
  public static function addToDictionary(string $language, string $contextInner, string $string): void;
  public function translate(string $string, array $vars = [], string $context = 'root'): string;
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

}
