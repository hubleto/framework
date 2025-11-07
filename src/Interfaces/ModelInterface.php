<?php

namespace Hubleto\Framework\Interfaces;

interface ModelInterface
{

  public function initRecordManager(): null|object;
  public function isDatabaseConnected(): bool;

  //////////////////////////////////////////////////////////////////
  // Methods for accessing and modifying model's config

  public function getConfigFullPath(string $configName): string;
  public function configAsString(string $configName): string;
  public function configAsInteger(string $configName): int;
  public function configAsArray(string $configName): array;

  //////////////////////////////////////////////////////////////////
  // SQL table manipulation

  public function getSqlCreateTableCommands(): array;
  public function createSqlTable();
  public function install();
  public function dropTableIfExists(): ModelInterface;
  public function createSqlForeignKeys();
  public function getFullTableSqlName();

  //////////////////////////////////////////////////////////////////
  // Methods for accessing information about model's columns
  // and indexes

  public function hasColumn(string $column): bool;
  public function getColumns(): array;
  public function getColumn(string $column): ColumnInterface;
  public function columnNames(): array;
  public function indexes(array $indexes = []): array;
  public function indexNames(): array;
  public function upgrades(): array;

  //////////////////////////////////////////////////////////////////
  // Description API

  public function describeColumns(): array;
  public function describeInput(string $columnName): \Hubleto\Framework\Description\Input;
  public function describeForm(): \Hubleto\Framework\Description\Form;
  public function describeTable(): \Hubleto\Framework\Description\Table;

  //////////////////////////////////////////////////////////////////
  // Record-related methods

  // public function recordGet(callable|null $queryModifierCallback = null): array;
  public function loadTableData(string $fulltextSearch = '', array $columnSearch = [], array $orderBy = [], int $itemsPerPage = 15, int $page = 0, string $dataView = ''): array;
  public function diffRecords(array $record1, array $record2): array;
  public function getById(int $id);
  public function getLookupSqlValue(string $tableAlias = ''): string;
  public function encryptPassword(string $original): string;

  //////////////////////////////////////////////////////////////////
  // Callbacks

  public function onBeforeCreate(array $record): array;
  public function onBeforeUpdate(array $record): array;
  public function onAfterCreate(array $savedRecord): array;
  public function onAfterUpdate(array $originalRecord, array $savedRecord): array;
  public function onBeforeDelete(int $id): int;
  public function onAfterDelete(int $id): int;
  public function onAfterLoadRecord(array $record): array;
  public function onAfterLoadRecords(array $records): array;

}
