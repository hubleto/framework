<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Exceptions\NotEnoughPermissionsException;

/**
 * Record manager based on Laravel's Eloquent.
 */
class EloquentRecordManager extends \Illuminate\Database\Eloquent\Model implements Interfaces\RecordManagerInterface {
  protected $primaryKey = 'id';
  protected $guarded = [];
  public $timestamps = false;
  public static $snakeAttributes = false;

  public Loader $main;
  public Model $model;

  public int $maxReadLevel = 2;

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
  }

  /**
   * Gets permissions for the given record.
   *
   * @param array $record Record to check permissions for.
   *
   * @return array
   *
   */
  public function getPermissions(array $record): array
  {
    return [true, true, true, true];
  }

  /**
   * [Description for prepareSelectsForReadQuery]
   *
   * @param mixed|null $query
   * @param int $level
   * @param array|null|null $includeRelations
   * 
   * @return array
   * 
   */
  public function prepareSelectsForReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): array
  {
    $selects = [];

    foreach ($this->model->getColumns() as $colName => $column) {
      $colDefinition = $column->toArray();
      if ((bool) ($colDefinition['hidden'] ?? false)) continue;

      if ($colDefinition['type'] == 'virtual') {
        $virtSql = $column->getProperty('sql');
        if (!empty($virtSql)) $selects[] = '(' . $virtSql . ') as `' . $colName . '`';
      } else {
        $selects[] = $this->model->table . '.' . $colName;

        if (isset($colDefinition['enumValues']) && is_array($colDefinition['enumValues'])) {
          $tmpSelect = "CASE";
          foreach ($colDefinition['enumValues'] as $eKey => $eVal) {
            $tmpSelect .= " WHEN `{$this->model->table}`.`{$colName}` = '{$eKey}' THEN '{$eVal}'";
          }
          $tmpSelect .= " ELSE '' END AS `_ENUM[{$colName}]`";

          $selects[] = $tmpSelect;
        }
      }
    }

    $selects[] = $level . ' as _LEVEL';
    $selects[] = '(' . str_replace('{%TABLE%}', $this->model->table, $this->model->getLookupSqlValue()) . ') as _LOOKUP';

    // LOOKUPS and RELATIONSHIPS
    foreach ($this->model->getColumns() as $columnName => $column) {
      $colDefinition = $column->toArray();
      if ($colDefinition['type'] == 'lookup') {
        $lookupModel = $this->model->getModel($colDefinition['model']);

        $selects[] =
          "(select _LOOKUP from ("
          . $lookupModel->record->prepareLookupQuery('')->toRawSql()
          . ") dummy where `id` = `{$this->table}`.`{$columnName}`) as `_LOOKUP[{$columnName}]`"
        ;
        $selects[] =
          "(select _LOOKUP_CLASS from ("
          . $lookupModel->record->prepareLookupQuery('')->toRawSql()
          . ") dummy where `id` = `{$this->table}`.`{$columnName}`) as `_LOOKUP_CLASS[{$columnName}]`"
        ;
        $selects[] =
          "(select _LOOKUP_COLOR from ("
          . $lookupModel->record->prepareLookupQuery('')->toRawSql()
          . ") dummy where `id` = `{$this->table}`.`{$columnName}`) as `_LOOKUP_COLOR[{$columnName}]`"
        ;
      }
    }

    return $selects;
  }

  /**
   * [Description for prepareJoinsForReadQuery]
   *
   * @param mixed|null $query
   * @param int $level
   * @param array|null|null $includeRelations
   * 
   * @return array
   * 
   */
  public function prepareJoinsForReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): array
  {
    $joins = [];

    // LOOKUPS and RELATIONSHIPS
    foreach ($this->model->getColumns() as $columnName => $column) {
      $colDefinition = $column->toArray();
      if ($colDefinition['type'] == 'lookup') {
        $lookupModel = $this->model->getModel($colDefinition['model']);
        $lookupDatabase = $lookupModel->record->getConnection()->getDatabaseName();
        $lookupTableName = $lookupModel->getFullTableSqlName();
        $joinAlias = 'join_' . $columnName;

        $joins[] = [
          $lookupDatabase . '.' . $lookupTableName . ' as ' . $joinAlias,
          $joinAlias . '.id',
          '=',
          $this->table . '.' . $columnName
        ];
      }
    }

    return $joins;
  }

  /**
   * [Description for prepareRelationsForReadQuery]
   *
   * @param mixed|null $query
   * @param int $level
   * @param array|null|null $includeRelations
   * 
   * @return array
   * 
   */
  public function prepareRelationsForReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {

    foreach ($this->model->relations as $relName => $relDefinition) {
      if (is_array($includeRelations) && !in_array($relName, $includeRelations)) continue;

      $relModel = new $relDefinition[1]();

      if ($level < $this->maxReadLevel) {
        $query->with([$relName => function($q) use($relModel, $level) {
          return $relModel->record->prepareReadQuery($q, $level + 1);
        }]);
      }
    }

    return $query;
  }

  /**
   * Prepares the read query for fetching records.
   *
   * @param mixed|null $query Leave empty for default behaviour.
   * @param int $level Level of recursion for including relations.
   * @param array|null $includeRelations If not null, only these relations will be included in the read query.
   *
   * @return mixed
   *
   */
  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    if ($query === null) $query = $this;

    $selects = $this->prepareSelectsForReadQuery($query, $level, $includeRelations);
    $joins = $this->prepareJoinsForReadQuery($query, $level, $includeRelations);

    // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
    $query = $query->selectRaw(join(",\n", $selects));

    foreach ($joins as $join) {
      $query->leftJoin($join[0], $join[1], $join[2], $join[3]);
    }

    $query = $this->prepareRelationsForReadQuery($query, $level, $includeRelations);

    return $query;
  }

  /**
   * [Description for recordGet]
   *
   * @param callable|null|null $queryModifierCallback
   *
   * @return array
   *
   */
  public function recordGet(callable|null $queryModifierCallback = null): array
  {
    $query = $this->prepareReadQuery();
    if ($queryModifierCallback !== null) $queryModifierCallback($query);
    $record = $this->recordRead($query);
    $record = $this->model->onAfterLoadRecord($record);
    return $record;
  }

  /**
   * Prepares the lookup query for fetching records.
   *
   * @param string $search String to filter lookup values.
   *
   * @return mixed
   *
   */
  public function prepareLookupQuery(string $search): mixed
  {
    $query = $this;

    if (!empty($search)) {
      $query = $query->having('_LOOKUP', 'like', '%'.$search.'%');
    }

    $selectRaw = [];
    $selectRaw[] = $this->table . '.*';
    $selectRaw[] = '(' . str_replace('{%TABLE%}', $this->table, $this->model->getLookupSqlValue()) . ') as _LOOKUP';
    $selectRaw[] = '"" as _LOOKUP_CLASS';

    if ($this->model->hasColumn('color')) {
      $selectRaw[] = 'color as _LOOKUP_COLOR';
    } else {
      $selectRaw[] = '"" as _LOOKUP_COLOR';
    }

    $query = $query->selectRaw(join(',', $selectRaw));

    foreach ($this->model->relations as $relationName => $relation) $query = $query->with($relationName);

    return $query;
  }

  /**
   * [Description for prepareLookupData]
   *
   * @param array $dataRaw
   *
   * @return array
   *
   */
  public function prepareLookupData(array $dataRaw): array
  {
    $data = [];

    foreach ($dataRaw as $key => $value) {
      $data[$key]['_LOOKUP'] = $this->model->getLookupValue($value);
      if (!empty($value['_LOOKUP_CLASS'])) $data[$key]['_LOOKUP_CLASS'] = $value['_LOOKUP_CLASS'];
      if (!empty($value['_LOOKUP_COLOR'])) $data[$key]['_LOOKUP_COLOR'] = $value['_LOOKUP_COLOR'];
      if (isset($value['id'])) {
        $data[$key]['id'] = Helper::encrypt($value['id']);
      }
      if (!empty($this->model->lookupUrlDetail)) {
        $data[$key]['_URL_DETAIL'] = str_replace('{%ID%}', $value['id'], $this->model->lookupUrlDetail);
      }
    }

    return $data;
  }

  /**
   * [Description for addFulltextSearchToQuery]
   *
   * @param mixed $query
   * @param string $fulltextSearch
   *
   * @return mixed
   *
   */
  public function addFulltextSearchToQuery(mixed $query, string $fulltextSearch): mixed
  {
    if (!empty($fulltextSearch)) {
      foreach ($this->model->getColumns() as $columnName => $column) {
        $enumValues = $column->getEnumValues();
        if (count($enumValues) > 0) {
          $query->orHaving('_ENUM[' . $columnName . ']', 'like', "%{$fulltextSearch}%");
        } else if ($column->getType() == 'lookup') {
          $query->orHaving('_LOOKUP[' . $columnName . ']', 'like', "%{$fulltextSearch}%");
        } else {
          $query->orHaving($columnName, 'like', "%{$fulltextSearch}%");
        }
      }
    }

    return $query;
  }

  /**
   * [Description for addColumnSearchToQuery]
   *
   * @param mixed $query
   * @param array $columnSearch
   *
   * @return mixed
   *
   */
  public function addColumnSearchToQuery(mixed $query, array $columnSearch): mixed
  {
    if (count($columnSearch) > 0) {
      foreach ($this->model->getColumns() as $columnName => $column) {
        if (!empty($columnSearch[$columnName])) {
          $searchValues = [];
          $searchGlue = 'OR';

          if (is_array($columnSearch[$columnName])) {
            $searchValues = $columnSearch[$columnName];
            $searchGlue = array_shift($searchValues);
          } else {
            $searchValues = [ $columnSearch[$columnName] ];
          }

          $query = $query->having(function($q) use ($column, $columnName, $searchValues, $searchGlue) {
            foreach ($searchValues as $searchValue) {
              $enumValues = $column->getEnumValues();
              if (count($enumValues) > 0) {
                switch ($searchGlue) {
                  case 'OR':
                  default:
                    $q = $q->orHaving('_ENUM[' . $columnName . ']', 'like', "%{$searchValue}%");
                  break;
                  case 'AND':
                    $q = $q->having('_ENUM[' . $columnName . ']', 'like', "%{$searchValue}%");
                  break;
                }
              } else if ($column->getType() == 'lookup') {
                switch ($searchGlue) {
                  case 'OR':
                  default:
                    $q = $q->orHaving('_LOOKUP[' . $columnName . ']', 'like', "%{$searchValue}%");
                  break;
                  case 'AND':
                    $q = $q->having('_LOOKUP[' . $columnName . ']', 'like', "%{$searchValue}%");
                  break;
                }
              } else if (in_array($column->getType(), ['int', 'decimal', 'float'])) {
                $tmpSearchValue = trim(str_replace(' ', '', str_replace(',', '.', $searchValue)));
                preg_match('/(.*?)([\\d\\.]+)/', $tmpSearchValue, $m);

                $operation = $m[1];
                $value = (float) $m[2];

                switch ($searchGlue) {
                  case 'OR':
                  default:
                    $q = $q->orHaving($columnName, $operation, $value);
                  break;
                  case 'AND':
                    $q = $q->having($columnName, $operation, $value);
                  break;
                }
              } else if (in_array($column->getType(), ['date', 'datetime', 'time'])) {
                if (is_array($searchValue)) {
                  if (count($searchValue) == 1) {
                    $from = $to = $searchValue[0];
                  } else if (count($searchValue) == 2) {
                    list($from, $to) = $searchValue;
                  }

                  $q->having($columnName, '>=', date('Y-m-d 00:00:00', strtotime((string) $from)));
                  $q->having($columnName, '<=', date('Y-m-d 23:59:59', strtotime((string) $to)));
                }
              } else if (in_array($column->getType(), ['boolean'])) {
                switch ($searchGlue) {
                  case 'OR':
                  default:
                    $q = $q->orHaving($columnName, $searchValue === "true");
                  break;
                  case 'AND':
                    $q = $q->having($columnName, $searchValue === "true");
                  break;
                }
              } else {
                switch ($searchGlue) {
                  case 'OR':
                  default:
                    $q = $q->orHaving($columnName, 'like', "%{$searchValue}%");
                  break;
                  case 'AND':
                    $q = $q->having($columnName, 'like', "%{$searchValue}%");
                  break;
                }
              }
            }
          });
        }
      }
    }

    return $query;
  }

  /**
   * [Description for addOrderByToQuery]
   *
   * @param mixed $query
   * @param array $orderBy
   *
   * @return mixed
   *
   */
  public function addOrderByToQuery(mixed $query, array $orderBy): mixed
  {
    if (isset($orderBy['field']) && isset($orderBy['direction'])) {
      $query->orderBy($orderBy['field'], $orderBy['direction']);
    }

    return $query;
  }

  /**
   * [Description for recordReadMany]
   *
   * @param mixed $query
   * @param int $itemsPerPage
   * @param int $page
   *
   * @return array
   *
   */
  public function recordReadMany(mixed $query, int $itemsPerPage, int $page): array
  {
    $data = $query->paginate(
      $itemsPerPage,
      ['*'],
      'page',
      $page
    )->toArray();

    foreach ($data['data'] as $key => $record) {
      $data['data'][$key]['_PERMISSIONS'] = $this->getPermissions($record);
    }

    // Laravel pagination
    if (!is_array($data)) $data = [];
    if (!is_array($data['data'])) $data['data'] = [];

    return $data;
  }

  /**
   * [Description for recordRead]
   *
   * @param mixed $query
   *
   * @return array
   *
   */
  public function recordRead(mixed $query): array {
    $record = $query->first()?->toArray();
    if (!is_array($record)) $record = [];

    $permissions = $this->getPermissions($record);
    if (!$permissions[1]) {
      // cannot read
      // $record = [];
      throw new NotEnoughPermissionsException("Cannot read record. Not enough permissions.");
    };

    if ($record != []) {
      $record = $this->recordEncryptIds($record);
      $record['_PERMISSIONS'] = $permissions;
      $record['_RELATIONS'] = array_keys($this->model->relations);
    }
    // if (count($this->relationsToRead) > 0) {
    //   $record['_RELATIONS'] = array_values(array_intersect($record['_RELATIONS'], $this->relationsToRead));
    // }

    return $record;
  }

  /**
   * [Description for recordEncryptIds]
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function recordEncryptIds(array $record): array
  {

    foreach ($this->model->getColumns() as $colName => $column) {
      $colDefinition = $column->toArray();
      if (($colName == 'id' || $colDefinition['type'] == 'lookup') && isset($record[$colName]) && $record[$colName] !== null) {
        $record[$colName] = Helper::encrypt($record[$colName]);
      }
    }

    $record['_idHash_'] =  Helper::encrypt($record['id'] ?? '', '', true);

    return $record;
  }

  /**
   * [Description for recordDecryptIds]
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function recordDecryptIds(array $record): array
  {
    foreach ($this->model->getColumns() as $colName => $column) {
      $colDefinition = $column->toArray();
      if ($colName == 'id' || $colDefinition['type'] == 'lookup') {
        if (isset($record[$colName]) && $record[$colName] !== null && is_string($record[$colName])) {
          $record[$colName] = Helper::decrypt($record[$colName]);
        }
      }
    }

    foreach ($this->model->relations as $relName => $relDefinition) {
      if (!isset($record[$relName]) || !is_array($record[$relName])) continue;

      list($relType, $relModelClass) = $relDefinition;
      $relModel = new $relModelClass();

      switch ($relType) {
        case Model::HAS_MANY:
          foreach ($record[$relName] as $subKey => $subRecord) {
            $record[$relName][$subKey] = $relModel->record->recordDecryptIds($record[$relName][$subKey]);
          }
        break;
        case Model::HAS_ONE:
          $record[$relName] = $relModel->record->recordDecryptIds($record[$relName]);
        break;
      }
    }

    return $record;
  }

  /**
   * [Description for recordCreate]
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function recordCreate(array $record, $useProvidedRecordId = false): array
  {
    $record = $this->model->onBeforeCreate($record);
    if (!$useProvidedRecordId) unset($record['id']);
    $normalizedRecord = $this->recordNormalize($record);
    $record['id'] = $this->create($normalizedRecord)->id;
    $record = $this->model->onAfterCreate($record);
    return $record;
  }

  /**
   * [Description for recordUpdate]
   *
   * @param array $record
   * @param array $originalRecord
   *
   * @return array
   *
   */
  public function recordUpdate(array $record, array $originalRecord = []): array
  {
    $record = $this->model->onBeforeUpdate($record);
    $normalizedRecord = $this->recordNormalize($record);
    $this->find((int) ($record['id'] ?? 0))->update($normalizedRecord);
    $record = $this->model->onAfterUpdate($originalRecord, $record);
    return $record;
  }

  /**
   * [Description for recordDelete]
   *
   * @param int|string $id
   *
   * @return int
   *
   */
  public function recordDelete(int|string $id): int
  {
    $this->model->onBeforeDelete((int) $id);

    $record = $this->recordRead($this->where('id', $id));
    $permissions = $this->getPermissions($record);
    if (!$permissions[3]) { // cannot delete
      throw new Exceptions\NotEnoughPermissionsException("Cannot delete. Not enough permissions.");
    }

    $rowsDeleted = $this->where('id', $id)->delete();

    $this->model->onAfterDelete((int) $id);

    return $rowsDeleted;
  }

  /**
   * [Description for recordSave]
   *
   * @param array $record
   * @param int $idMasterRecord
   * @param array $saveRelations
   * @param string $relation
   *
   * @return array
   *
   */
  public function recordSave(array $record, int $idMasterRecord = 0, array $saveRelations = [], string $relation = ''): array
  {

    $id = (int) ($record['id'] ?? 0);
    $isCreate = ($id <= 0);

    if ($id <= 0) {
      $permissions = $this->getPermissions($record);
    } else {
      $originalRecord = $this->where($this->table . '.id', $id)->first()?->toArray();
      $permissions = $this->getPermissions($originalRecord);
    }

    if (
      ($id < 0 && !$permissions[0]) // cannot create
      || ($id >= 0 && !$permissions[2]) // cannot update
    ) {
      throw new \Hubleto\Framework\Exceptions\NotEnoughPermissionsException("Cannot save. Not enough permissions.");
    }

    $savedRecord = $record;
    if ($idMasterRecord == 0) $this->recordValidate($savedRecord, $saveRelations);

    try {

      $columns = $this->model->getColumns();

      foreach ($savedRecord as $key => $value) {
        $useMasterRecordId = false;
        if (isset($value['_useMasterRecordId_'])) $useMasterRecordId = $value['_useMasterRecordId_'];
        if (isset($columns[$key]) && is_array($value) && $useMasterRecordId) {
          $savedRecord[$key] = $idMasterRecord;
        }
      }

      if ((bool) ($record['_toBeDeleted_'] ?? false)) {
        $this->recordDelete((int) $savedRecord['id']);
        return [];
      } else if ($isCreate) {
        $savedRecord = $this->recordCreate($savedRecord);
      } else {
        $savedRecord = $this->recordUpdate($savedRecord, $originalRecord);
      }

      foreach ($this->model->relations as $relName => $relDefinition) {
        $tmpRelation = $relation . ($relation == '' ? '' : '.') . $relName;

        if (!in_array($tmpRelation, $saveRelations)) continue;

        if (isset($record[$relName]) && is_array($record[$relName])) {
          list($relType, $relModelClass) = $relDefinition;
          $relModel = new $relModelClass();
          switch ($relType) {
            case Model::HAS_MANY:
              foreach ($record[$relName] as $subKey => $subRecord) {
                if (is_array($subRecord)) {
                  $subRecord = $relModel->record->recordSave($subRecord, $savedRecord['id'], $saveRelations, $tmpRelation);
                  $savedRecord[$relName][$subKey] = $subRecord;
                }
              }
            break;
            case Model::HAS_ONE:
              if (is_array($record[$relName])) {
                $subRecord = $relModel->record->recordSave($record[$relName], $savedRecord['id'], $saveRelations, $tmpRelation);
                $savedRecord[$relName] = $subRecord;
              }
            break;
          }
        }
      }

    } catch (\Illuminate\Database\QueryException $e) {
      if ($e->getCode() == 23000) {
        $errorMessage = "A field contains a value that already exists.";

        throw new Exceptions\DBDuplicateEntryException($errorMessage, $e->getCode(), $e);
      } else {
        throw new Exceptions\DBException($e->getMessage(), $e->getCode(), $e);
      }
    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
      if ($e->errorInfo[1] == 1062) {
        $columns = $this->model->getColumns();

        preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $e->errorInfo[2], $m);
        $invalidIndex = $m[2];
        $invalidValue = $m[1];
        $invalidIndexName = $columns[$invalidIndex]->getTitle();

        $errorMessage = "Value '{$invalidValue}' for {$invalidIndexName} already exists.";

        throw new Exceptions\DBException($errorMessage, $e->getCode(), $e);
      } else {
        throw new Exceptions\DBException($e->getMessage(), $e->getCode(), $e);
      }
    }

    return $savedRecord;
  }

  /**
   * [Description for recordValidate]
   *
   * @param array $record
   * @param array $validateRelations
   * @param string $relation
   *
   * @return array
   *
   */
  public function recordValidate(array $record, array $validateRelations = [], string $relation = ''): array
  {
    $readableInvalidInputs = [];
    $invalidInputs = [];

    foreach ($this->model->getColumns() as $colName => $column) {
      if (
        $column->getRequired()
        && (!isset($record[$colName]) || $column->isEmpty($record[$colName]))
      ) {
        $readableInvalidInputs[] = $this->model->shortName . "." . $column->getTitle() ." is required.";

        $invalidInputs[] = ['name' => $this->model->shortName . "." . $colName, 'id' => $record['id'] ?? -1];
      } else if (isset($record[$colName]) && !$column->validate($record[$colName])) {
        $readableInvalidInputs[] = $this->model->shortName . "." . $column->getTitle() ." contains invalid value.";

        // todo
        $invalidInputs[] = ['name' => $this->model->shortName . "." . $colName, 'id' => $record['id'] ?? -1];
      }
    }

    if (!empty($readableInvalidInputs)) {
      throw new Exceptions\RecordSaveException(join("\n", $readableInvalidInputs), $invalidInputs);
    }

    foreach ($this->model->relations as $relName => $relDefinition) {
      $tmpRelation = $relation . ($relation == '' ? '' : '.') . $relName;

      if (!in_array($tmpRelation, $validateRelations)) continue;

      if (isset($record[$relName]) && is_array($record[$relName])) {
        list($relType, $relModelClass) = $relDefinition;
        $relModel = new $relModelClass();
        switch ($relType) {
          case Model::HAS_MANY:
            foreach ($record[$relName] as $subKey => $subRecord) {
              if (is_array($subRecord)) {
                $subRecord = $relModel->record->recordValidate($subRecord, $validateRelations, $tmpRelation);
              }
            }
          break;
          case Model::HAS_ONE:
            if (is_array($record[$relName])) {
              $subRecord = $relModel->record->recordValidate($record[$relName], $validateRelations, $tmpRelation);
            }
          break;
        }
      }
    }

    return $record;
  }

  /**
   * [Description for recordNormalize]
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function recordNormalize(array $record): array {
    $columns = $this->model->getColumns();

    foreach ($record as $colName => $colValue) {
      if (!isset($columns[$colName])) {
        unset($record[$colName]);
      } else {
        $colDefinition = $columns[$colName]->toArray();
        if ($colDefinition['type'] == 'virtual') {
          unset($record[$colName]);
        } else {
          $record[$colName] = $columns[$colName]->normalize($record[$colName]);
          if ($record[$colName] === null) unset($record[$colName]);
        }
      }
    }

    foreach ($columns as $colName => $column) {
      if (!isset($record[$colName])) {
        $nullValue = $column->getNullValue();
        if ($nullValue !== null) $record[$colName] = $nullValue;
      }
    }

    return $record;
  }

}
