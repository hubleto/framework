<?php

namespace Hubleto\Framework;

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

  // /** What relations to be included in loaded record. If null, default relations will be selected. */
  // /** @property array<string> */
  // protected array $relationsToRead = [];

  protected int $maxReadLevel = 2;

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
  }

  public function getPermissions(array $record): array
  {
    return [true, true, true, true];
  }

  /**
   * prepareReadQuery
   * @param mixed $query Leave empty for default behaviour.
   * @param int $level Leave empty for default behaviour.
   * @return mixed Eloquent query used to read record.
   */
  public function prepareReadQuery(mixed $query = null, int $level = 0): mixed
  {
    if ($query === null) $query = $this;

    $selectRaw = [];
    $withs = [];
    $joins = [];

    foreach ($this->model->getColumns() as $colName => $column) {
      $colDefinition = $column->toArray();
      if ((bool) ($colDefinition['hidden'] ?? false)) continue;

      if ($colDefinition['type'] == 'virtual') {
        $virtSql = $column->getProperty('sql');
        if (!empty($virtSql)) $selectRaw[] = '(' . $virtSql . ') as `' . $colName . '`';
      } else {
        $selectRaw[] = $this->model->table . '.' . $colName;

        if (isset($colDefinition['enumValues']) && is_array($colDefinition['enumValues'])) {
          $tmpSelect = "CASE";
          foreach ($colDefinition['enumValues'] as $eKey => $eVal) {
            $tmpSelect .= " WHEN `{$this->model->table}`.`{$colName}` = '{$eKey}' THEN '{$eVal}'";
          }
          $tmpSelect .= " ELSE '' END AS `_ENUM[{$colName}]`";

          $selectRaw[] = $tmpSelect;
        }
      }
    }

    $selectRaw[] = $level . ' as _LEVEL';
    $selectRaw[] = '(' . str_replace('{%TABLE%}', $this->model->table, $this->model->getLookupSqlValue()) . ') as _LOOKUP';

    // LOOKUPS and RELATIONSHIPS
    foreach ($this->model->getColumns() as $columnName => $column) {
      $colDefinition = $column->toArray();
      if ($colDefinition['type'] == 'lookup') {
        $lookupModel = $this->model->getModel($colDefinition['model']);
        // $lookupConnection = $lookupModel->record->getConnectionName();
        $lookupDatabase = $lookupModel->record->getConnection()->getDatabaseName();
        $lookupTableName = $lookupModel->getFullTableSqlName();
        $joinAlias = 'join_' . $columnName;

        $selectRaw[] =
          "(select _LOOKUP from ("
          . $lookupModel->record->prepareLookupQuery('')->toRawSql()
          . ") dummy where `id` = `{$this->table}`.`{$columnName}`) as `_LOOKUP[{$columnName}]`"
        ;
        $selectRaw[] =
          "(select _LOOKUP_CLASS from ("
          . $lookupModel->record->prepareLookupQuery('')->toRawSql()
          . ") dummy where `id` = `{$this->table}`.`{$columnName}`) as `_LOOKUP_CLASS[{$columnName}]`"
        ;
        $selectRaw[] =
          "(select _LOOKUP_COLOR from ("
          . $lookupModel->record->prepareLookupQuery('')->toRawSql()
          . ") dummy where `id` = `{$this->table}`.`{$columnName}`) as `_LOOKUP_COLOR[{$columnName}]`"
        ;

        $joins[] = [
          $lookupDatabase . '.' . $lookupTableName . ' as ' . $joinAlias,
          $joinAlias.'.id',
          '=',
          $this->table.'.'.$columnName
        ];
      }
    }

    // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
    $query = $query->selectRaw(join(",\n", $selectRaw)); //->with($withs);
    foreach ($this->model->relations as $relName => $relDefinition) {
      // if (count($this->relationsToRead) > 0 && !in_array($relName, $this->relationsToRead)) continue;

      $relModel = new $relDefinition[1]();

      if ($level < $this->maxReadLevel) {
        $query->with([$relName => function($q) use($relModel, $level) {
          return $relModel->record->prepareReadQuery($q, $level + 1);
        }]);
      }
    }

    foreach ($joins as $join) {
      $query->leftJoin($join[0], $join[1], $join[2], $join[3]);
    }

    return $query;
  }

  /**
   * prepareLookupQuery
   * @param string $searc What string to lookup for
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

  public function addColumnSearchToQuery(mixed $query, array $columnSearch): mixed
  {
    if (count($columnSearch) > 0) {
      foreach ($this->model->getColumns() as $columnName => $column) {
        if (!empty($columnSearch[$columnName])) {
          $enumValues = $column->getEnumValues();
          if (count($enumValues) > 0) {
            $query->having('_ENUM[' . $columnName . ']', 'like', "%{$columnSearch[$columnName]}%");
          } else if ($column->getType() == 'lookup') {
            $query->having('_LOOKUP[' . $columnName . ']', 'like', "%{$columnSearch[$columnName]}%");
          } else if (in_array($column->getType(), ['int', 'decimal', 'float'])) {
            $q = trim(str_replace(' ', '', str_replace(',', '.', $columnSearch[$columnName])));

            preg_match('/(.*?)([\\d\\.]+)/', $q, $m);

            $operation = $m[1];
            $value = (float) $m[2];

            $query->where($columnName, $operation, $value);
          } else if (in_array($column->getType(), ['date', 'datetime', 'time'])) {
            if (is_array($columnSearch[$columnName])) {
              if (count($columnSearch[$columnName]) == 1) {
                $from = $to = $columnSearch[$columnName][0];
              } else if (count($columnSearch[$columnName]) == 2) {
                list($from, $to) = $columnSearch[$columnName];
              }

              $query->having($columnName, '>=', date('Y-m-d 00:00:00', strtotime((string) $from)));
              $query->having($columnName, '<=', date('Y-m-d 23:59:59', strtotime((string) $to)));
            }
          } else if (in_array($column->getType(), ['boolean'])) {
            $query->having($columnName, $columnSearch[$columnName] === "true");
          } else {
            $query->having($columnName, 'like', "%{$columnSearch[$columnName]}%");
          }
        }
      }
    }

    return $query;
  }

  public function addOrderByToQuery(mixed $query, array $orderBy): mixed
  {
    if (isset($orderBy['field']) && isset($orderBy['direction'])) {
      $query->orderBy($orderBy['field'], $orderBy['direction']);
    }

    return $query;
  }

  public function recordReadMany(mixed $query, int $itemsPerPage, int $page): array
  {
    $data = $query->paginate(
      $itemsPerPage,
      ['*'],
      'page',
      $page
    )->toArray();

    foreach ($data['data'] as $key => $record) {
      $permissions = $this->getPermissions($record);
      if (!$permissions[1]) {
        // cannot read
        unset($data['data'][$key]);
      } else {
        $data['data'][$key]['_PERMISSIONS'] = $permissions;
      }
    }

    // Laravel pagination
    if (!is_array($data)) $data = [];
    if (!is_array($data['data'])) $data['data'] = [];

    return $data;
  }

  public function recordRead(mixed $query): array {
    $record = $query->first()?->toArray();
    if (!is_array($record)) $record = [];

    $permissions = $this->getPermissions($record);
    if (!$permissions[1]) {
      // cannot read
      $record = [];
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

  public function recordCreate(array $record): array
  {
    $record = $this->model->onBeforeCreate($record);
    unset($record['id']);
    $normalizedRecord = $this->recordNormalize($record);
    $record['id'] = $this->create($normalizedRecord)->id;
    $record = $this->model->onAfterCreate($record);
    return $record;
  }

  public function recordUpdate(array $record, array $originalRecord = []): array
  {
    // $originalRecord = $record;
    $record = $this->model->onBeforeUpdate($record);
    $normalizedRecord = $this->recordNormalize($record);
    $this->find((int) ($record['id'] ?? 0))->update($normalizedRecord);
    $record = $this->model->onAfterUpdate($originalRecord, $record);
    return $record;
  }

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
      throw new Exceptions\DBException($e->getMessage(), $e->getCode(), $e);
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
   * validate
   * @param array<string, mixed> $record
   * @return array<string, mixed>
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

        $invalidInputs[] = $this->model->shortName . "." . $column->getTitle();
      } else if (isset($record[$colName]) && !$column->validate($record[$colName])) {
        $readableInvalidInputs[] = $this->model->shortName . "." . $column->getTitle() ." contains invalid value.";

        $invalidInputs[] = $this->model->shortName . "." . $column->getTitle();
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
