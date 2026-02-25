<?php

namespace Hubleto\Framework\Enums;

enum InstalledMigrationEnum
{
  case TABLES;
  case FOREIGN_KEYS;

  public function toString(): string
  {
    return match ($this) {
      InstalledMigrationEnum::TABLES => 'installed-migration-tables',
      InstalledMigrationEnum::FOREIGN_KEYS => 'installed-migration-foreign-keys',
    };
  }
}
