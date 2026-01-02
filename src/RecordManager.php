<?php

namespace Hubleto\Framework;

/**
 * Default record manager for Hubleto projects. Uses Laravel's Eloquent.
 */
class RecordManager extends EloquentRecordManager
{

  public function getPermissions(array $record): array
  {
    // by default, restrict all CRUD operations
    return [false, false, false, false];
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    return parent::prepareReadQuery($query, $level, $includeRelations);
  }
}
