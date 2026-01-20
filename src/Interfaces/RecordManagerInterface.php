<?php

namespace Hubleto\Framework\Interfaces;

/**
  * Record-management
  * CRUD-like layer for manipulating records (data)
*/

interface RecordManagerInterface {


  public function loader(): CoreInterface;

  /**
   * prepareReadQuery
   * @param mixed $query Leave empty for default behaviour.
   * @param int $level Leave empty for default behaviour.
   * @return mixed Object for reading records.
   */
  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed;
  public function addFulltextSearchToQuery(mixed $query, string $fulltextSearch): mixed;
  public function addColumnSearchToQuery(mixed $query, array $columnSearch): mixed;
  public function addOrderByToQuery(mixed $query, array $orderBy): mixed;
  public function recordReadMany(mixed $query, int $itemsPerPage, int $page): array;
  public function recordRead(mixed $query): array;
  public function recordReadById(int $id);

  public function recordEncryptIds(array $record): array;
  public function recordDecryptIds(array $record): array;
  public function recordCreate(array $record, $useProvidedRecordId = false): array;
  public function recordUpdate(array $record, array $originalRecord = []): array;
  public function recordDelete(int|string $id): int;
  public function recordSave(array $record, int $idMasterRecord = 0): array;

  public function loadFormData(int $id): array;
  public function loadTableData(string $fulltextSearch = '', array $columnSearch = [], array $orderBy = [], int $itemsPerPage = 15, int $page = 0, string $dataView = ''): array;

  /**
   * validate
   * @param array<string, mixed> $record
   * @return array<string, mixed>
   */
  public function recordValidate(array $record): array;
  public function recordNormalize(array $record): array;

}