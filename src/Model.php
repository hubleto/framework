<?php

namespace Hubleto\Framework;

use Hubleto\Framework\Exceptions\DBException;
use ReflectionClass;

/**
 * Default implementation of model for Hubleto project.
 */
class Model extends Core implements Interfaces\ModelInterface
{
  const HAS_ONE = 'hasOne';
  const HAS_MANY = 'hasMany';
  const HAS_MANY_THROUGH = 'hasManyThrough';
  const BELONGS_TO = 'belongsTo';

  /**
   * Full name of the model. Useful for getModel() function
   */
  public string $fullName = "";

  /**
   * Short name of the model. Useful for debugging purposes
   */
  public string $shortName = "";

  public \Illuminate\Database\Eloquent\Model|Interfaces\RecordManagerInterface $record;

  /**
   * SQL-compatible string used to render displayed value of the record when used
   * as a lookup.
   */
  public ?string $lookupSqlValue = NULL;

  public ?string $lookupUrlDetail = '';
  public ?string $lookupUrlAdd = '';

  /**
   * If set to TRUE, the SQL table will not contain the ID autoincrement column
   */
  public bool $isJunctionTable = FALSE;

  public string $sqlEngine = 'InnoDB';

  public string $table = '';
  public string $recordManagerClass = '';
  public array $relations = [];

  public ?array $junctions = [];

  /** @property array<string, \Hubleto\Framework\Interfaces\ColumnInterface> */
  protected array $columns = [];

  public bool $isExtendableByCustomColumns = false;

  public array $conversionRelations = [];
  public string $permission = '';
  public array $rolePermissions = []; // example: [ [UserRole::ROLE_CHIEF_OFFICER => [true, true, true, true]] ]

  public function __construct()
  {

    $reflection = new \ReflectionClass($this);
    preg_match('/^(.*?)\\\Models\\\(.*?)$/', $reflection->getName(), $m);
    if (isset($m[1]) && isset($m[2])) {
      $this->translationContext = str_replace('\\', '-', strtolower($m[1] . '\\Loader'));
      $this->translationContextInner = 'Models\\' . $m[2];
    }

    $recordManagerClass = $this->recordManagerClass;
    if (!empty($recordManagerClass) && $this->isDatabaseConnected()) {
      $this->record = $this->initRecordManager();
      $this->record->model = $this;
    }

    $this->fullName = $reflection->getName();

    $tmp = explode("\\", $this->fullName);
    $this->shortName = end($tmp);

    $this->columns = $this->describeColumns();

  }

  /**
   * [Description for initRecordManager]
   *
   * @return null|object
   * 
   */
  public function initRecordManager(): null|object
  {
    $recordManagerClass = $this->recordManagerClass;
    $recordManager = new $recordManagerClass();
    $recordManager->model = $this;
    return $recordManager;
  }

  /**
   * [Description for isDatabaseConnected]
   *
   * @return bool
   * 
   */
  public function isDatabaseConnected(): bool
  {
    return $this->db()->isConnected;
  }

  //////////////////////////////////////////////////////////////////
  // Methods for accessing and modifying model's config

  /**
   * [Description for getConfigFullPath]
   *
   * @param string $configName
   * 
   * @return string
   * 
   */
  public function getConfigFullPath(string $configName): string
  {
    return 'models/' . $this->fullName . '/' . $configName;
  }

  /**
   * Retrieves value of configuration parameter.
   *
   * @return void
   */
  public function configAsString(string $configName): string
  {
    return $this->config()->getAsString($this->getConfigFullPath($configName));
  }

  /**
   * Retrieves value of configuration parameter.
   *
   * @return void
   */
  public function configAsInteger(string $configName): int
  {
    return $this->config()->getAsInteger($this->getConfigFullPath($configName));
  }

  /**
   * Retrieves value of configuration parameter.
   *
   * @return void
   */
  public function configAsArray(string $configName): array
  {
    return $this->config()->getAsArray($this->getConfigFullPath($configName));
  }

  //////////////////////////////////////////////////////////////////
  // SQL table manipulation

  /**
   * [Description for getSqlCreateTableCommands]
   *
   * @return array
   * 
   */
  public function getSqlCreateTableCommands(): array
  {

    $columns = $this->columns;

    $createSql = "create table `{$this->table}` (\n";

    foreach ($columns as $columnName => $column) {
      $tmp = $column->sqlCreateString($this->table, $columnName);
      if (!empty($tmp)) $createSql .= " {$tmp},\n";

    }

    // indexy
    foreach ($columns as $columnName => $column) {
      $tmp = $column->sqlIndexString($this->table, $columnName);
      if (!empty($tmp)) $createSql .= " {$tmp},\n";
    }

    $createSql = substr($createSql, 0, -2) . ") ENGINE = {$this->sqlEngine}";

    $commands = [];
    $commands[] = "SET foreign_key_checks = 0";
    $commands[] = "drop table if exists `{$this->table}`";
    $commands[] = $createSql;
    $commands[] = "SET foreign_key_checks = 1";

    return $commands;

  }

  /**
   * [Description for createSqlTable]
   *
   * @return [type]
   * 
   */
  public function createSqlTable()
  {

    $this->db()->startTransaction();
    foreach ($this->getSqlCreateTableCommands() as $command) {
      $this->db()->execute($command);
    }
    $this->db()->commit();
  }

  /**
   * Installs the first version of the model into SQL database. Automatically creates indexes.
   *
   * @return void
   */
  public function install()
  {
    if (!empty($this->table)) {
      $this->createSqlTable();

      foreach ($this->indexes() as $indexOrConstraintName => $indexDef) {
        if (empty($indexOrConstraintName) || is_numeric($indexOrConstraintName)) {
          $indexOrConstraintName = md5(json_encode($indexDef) . uniqid());
        }

        $tmpColumns = "";

        foreach ($indexDef['columns'] as $tmpKey => $tmpValue) {
          if (!is_numeric($tmpKey)) {
            // v tomto pripade je nazov stlpca v kluci a vo value mozu byt dalsie nastavenia
            $tmpColumnName = $tmpKey;
            $tmpOrder = strtolower($tmpValue['order'] ?? 'asc');
            if (!in_array($tmpOrder, ['asc', 'desc'])) {
              $tmpOrder = 'asc';
            }
          } else {
            $tmpColumnName = $tmpValue;
            $tmpOrder = '';
          }

          $tmpColumns .=
            ($tmpColumns == '' ? '' : ', ')
            . '`' . $tmpColumnName . '`'
            . (empty($tmpOrder) ? '' : ' ' . $tmpOrder);
        }

        switch ($indexDef["type"]) {
          case "index":
            $this->db()->execute("
              alter table `" . $this->table . "`
              add index `{$indexOrConstraintName}` ({$tmpColumns})
            ");
            break;
          case "unique":
            $this->db()->execute("
              alter table `" . $this->table . "`
              add constraint `{$indexOrConstraintName}` unique ({$tmpColumns})
            ");
            break;
        }
      }

      $this->config()->save(
        'models/' . str_replace("/", "-", $this->fullName) . '/installed-upgrade',
        max(array_keys($this->upgrades()))
      );

      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * [Description for dropTableIfExists]
   *
   * @return Model
   * 
   */
  public function dropTableIfExists(): Interfaces\ModelInterface
  {
    $this->db()->execute("set foreign_key_checks = 0");
    $this->db()->execute("drop table if exists `" . $this->table . "`");
    $this->db()->execute("set foreign_key_checks = 1");
    return $this;
  }

  /**
   * Create foreign keys for the SQL table. Called when all models are installed.
   *
   * @return void
   */
  public function createSqlForeignKeys()
  {

    $sql = '';
    foreach ($this->getColumns() as $colName => $column) {
      $columnDefinition = $column->toArray();

      if (
        !($columnDefinition['disableForeignKey'] ?? false)
        && 'lookup' == $columnDefinition['type']
      ) {
        $lookupModel = $this->getModel($columnDefinition['model']);
        $foreignKeyColumn = $columnDefinition['foreignKeyColumn'] ?? "id";
        $foreignKeyOnDelete = $columnDefinition['foreignKeyOnDelete'] ?? "RESTRICT";
        $foreignKeyOnUpdate = $columnDefinition['foreignKeyOnUpdate'] ?? "RESTRICT";

        $sql .= "
          ALTER TABLE `{$this->table}`
          ADD CONSTRAINT `fk_" . md5($this->table . '_' . $colName) . "`
          FOREIGN KEY (`{$colName}`)
          REFERENCES `" . $lookupModel->getFullTableSqlName() . "` (`{$foreignKeyColumn}`)
          ON DELETE {$foreignKeyOnDelete}
          ON UPDATE {$foreignKeyOnUpdate};;
        ";
      }
    }

    if (!empty($sql)) {
      foreach (explode(';;', $sql) as $query) {
        $this->db()->execute(trim($query));
      }
    }

  }

  /**
   * Returns full name of the model's SQL table
   *
   * @return string Full name of the model's SQL table
   */
  public function getFullTableSqlName()
  {
    return $this->table;
  }

  /**
   * Returns list of available upgrades. This method must be overriden by each model.
   *
   * @return array List of available upgrades. Keys of the array are simple numbers starting from 1.
   */
  public function upgrades(): array
  {
    return [
      0 => [], // upgrade to version 0 is the same as installation
    ];
  }

  /**
   * [Description for getInstalledUpgrade]
   *
   * @return int
   * 
   */
  private function getInstalledUpgrade(): int
  {
    return $this->config()->getAsInteger('installed-upgrade', 0);
  }

  /**
   * [Description for getLatestUpgrade]
   *
   * @return int
   * 
   */
  private function getLatestUpgrade(): int
  {
    return max(array_keys($this->upgrades()));
  }

  /**
   * [Description for hasAvailableUpgrades]
   *
   * @return array
   * 
   */
  public function getAvailableUpgrades(): array
  {
    $availableUpgrades = [];
    $installedUpgrade = $this->getInstalledUpgrade();
    $latestUpgrade = $this->getLatestUpgrade();

    $upgrades = $this->upgrades();

    for ($v = $installedUpgrade + 1; $v <= $latestUpgrade; $v++) {
      $availableUpgrades[] = $upgrades[$v];
    }
    
    return $availableUpgrades;
  }

  /**
   * Installs all upgrades of the model. Internaly stores current version and
   * compares it to list of available upgrades.
   *
   * @return void
   * @throws DBException When an error occured during the upgrade.
   */
  public function installUpgrades(): void
  {
    $availableUpgrades = $this->getAvailableUpgrades();

    if (count($availableUpgrades) > 0) {
      try {
        $this->db()->startTransaction();

        foreach ($availableUpgrades as $upgrade) {
          $this->db()->execute($upgrade);
        }

        $this->db()->commit();
        $this->config()->save('installed-upgrade', $this->getLatestUpgrade());
      } catch (DBException $e) {
        $this->db()->rollback();
        throw new DBException($e->getMessage());
      }
    }
  }

  //////////////////////////////////////////////////////////////////
  // Methods for accessing information about model's columns
  // and indexes

  /**
   * [Description for hasColumn]
   *
   * @param string $column
   * 
   * @return bool
   * 
   */
  public function hasColumn(string $column): bool
  {
    return in_array($column, array_keys($this->getColumns()));
  }

  /**
   * [Description for getColumns]
   *
   * @return array<string, \Hubleto\Framework\Column>
   * 
   */
  public function getColumns(): array
  {
    return $this->columns;
  }

  /**
   * [Description for getColumn]
   *
   * @param string $column
   * 
   * @return Interfaces\ColumnInterface
   * 
   */
  public function getColumn(string $column): Interfaces\ColumnInterface
  {
    return $this->columns[$column];
  }

  /**
   * [Description for columnNames]
   *
   * @return array
   * 
   */
  public function columnNames(): array
  {
    return array_keys($this->columns);
  }

  /**
   * [Description for indexes]
   *
   * @param array $indexes
   * 
   * @return array
   * 
   */
  public function indexes(array $indexes = []): array
  {
    return $indexes;
  }

  /**
   * [Description for indexNames]
   *
   * @return array
   * 
   */
  public function indexNames(): array
  {
    return array_keys($this->indexNames());
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

  //////////////////////////////////////////////////////////////////
  // Description API

  /**
   * [Description for describeColumns]
   *
   * @return array
   * 
   */
  public function describeColumns(): array
  {
    $columns = [];

    if (!$this->isJunctionTable) {
      $columns['id'] = new \Hubleto\Framework\Db\Column\PrimaryKey($this, 'ID', 8);
    }

    if ($this->isExtendableByCustomColumns) {
      $columnsCfg = $this->configAsArray('customColumns') ?? [];
      foreach ($columnsCfg as $colName => $colCfg) {
        $colClass = $colCfg['class'] ?? '';
        if (class_exists($colClass)) {
          $columns[$colName] = (new $colClass($this, ''))->loadFromArray($colCfg)->setProperty('isCustom', true);
        }
      }
    }

    return $columns;
  }

  /**
   * [Description for describeInput]
   *
   * @param string $columnName
   * 
   * @return \Hubleto\Framework\Description\Input
   * 
   */
  public function describeInput(string $columnName): \Hubleto\Framework\Description\Input
  {
    return $this->columns[$columnName]->describeInput();
  }

  /**
   * [Description for describeForm]
   *
   * @return \Hubleto\Framework\Description\Form
   * 
   */
  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = new \Hubleto\Framework\Description\Form();

    $columnNames = $this->columnNames();

    $description->inputs = [];
    foreach ($columnNames as $columnName) {
      if ($columnName == 'id') continue;
      $inputDesc = $this->describeInput($columnName);
      $description->inputs[$columnName] = $inputDesc;
      if ($inputDesc->getDefaultValue() !== null) {
        $description->defaultValues[$columnName] = $inputDesc->getDefaultValue();
      }
    }

    // $description->permissions = [
    //   'canRead' => $this->permissionsManager()->granted($this->fullName . ':Read'),
    //   'canCreate' => $this->permissionsManager()->granted($this->fullName . ':Create'),
    //   'canUpdate' => $this->permissionsManager()->granted($this->fullName . ':Update'),
    //   'canDelete' => $this->permissionsManager()->granted($this->fullName . ':Delete'),
    // ];

    $description->includeRelations = array_keys($this->relations);

    $description->permissions = [
      'canRead' => true,
      'canCreate' => true,
      'canUpdate' => true,
      'canDelete' => true,
    ];

    return $description;
  }

  /**
   * Returns a table description of the model.
   * The descriptions contains configuration for table UI, columns and permissions.
   *
   * @return \Hubleto\Framework\Description\Table
   * 
   */
  public function describeTable(): \Hubleto\Framework\Description\Table
  {

    $columns = $this->columns;
    if (isset($columns['id'])) unset($columns['id']);

    $description = new \Hubleto\Framework\Description\Table();
    foreach ($columns as $columnName => $column) {
      if (!$column->getHidden()) {
        $description->columns[$columnName] = $column;
      }
    }

    $description->inputs = [];
    foreach ($columns as $columnName => $column) {
      if ($columnName == 'id') continue;
      $description->inputs[$columnName] = $this->describeInput($columnName);
    }

    // $description->permissions = [
    //   'canRead' => $this->permissionsManager()->granted($this->fullName . ':Read'),
    //   'canCreate' => $this->permissionsManager()->granted($this->fullName . ':Create'),
    //   'canUpdate' => $this->permissionsManager()->granted($this->fullName . ':Update'),
    //   'canDelete' => $this->permissionsManager()->granted($this->fullName . ':Delete'),
    // ];


    $tag = $this->router()->urlParamAsString('tag');

    $description->permissions = [
      'canRead' => true,
      'canCreate' => true,
      'canUpdate' => true,
      'canDelete' => true,
    ];

    $description->ui['moreActions'] = [
      [ 'title' => $this->translate('Export to CSV', [], 'hubleto-erp-loader:Model'), 'type' => 'stateChange', 'state' => 'showExportCsvScreen', 'value' => true ],
      [ 'title' => $this->translate('Import from CSV', [], 'hubleto-erp-loader:Model'), 'type' => 'stateChange', 'state' => 'showImportCsvScreen', 'value' => true ],
    ];

    if (!empty($tag)) {
      $description->ui['moreActions'][] = [
        'title' => 'Columns',
        'type' => 'stateChange',
        'state' => 'showColumnConfigScreen',
        'value' => true,
      ];

      // hide && re-order columns based on user config
      $allColumnsConfig = @json_decode($this->configAsString('tableColumns') ?? '', true);
      $columns = $description->columns;

      // hide always hidden columns
      foreach ($columns as $colName => $column) {
        if ($column->getVisibility() == Column::ALWAYS_HIDDEN) unset($columns[$colName]);
      }

      if (isset($allColumnsConfig[$tag])) {
        // re-order columns
        $columnsOrdered = [];
        foreach ($allColumnsConfig[$tag] as $colName => $is_hidden) {
          if (isset($columns[$colName]) && !$is_hidden) {
            $columnsOrdered[$colName] = $description->columns[$colName];
          }
        }
        $columns = $columnsOrdered;
      } else {
        // hide default non-visible columns
        foreach ($columns as $colName => $column) {
          if ($column->getVisibility() != Column::DEFAULT_VISIBLE) unset($columns[$colName]);
        }
      }
      
      // show always visible columns
      foreach ($description->columns as $colName => $column) {
        if ($column->getVisibility() == Column::ALWAYS_VISIBLE) $columns[$colName] = $column;
      }

      $description->columns = $columns;
    }

    return $description;
  }

  /**
   * Used to convert flat list of records into tree structure.
   * Suitable for models having parent-child relationship.
   *
   * @param array $records
   * @param int $idParent
   * @param int $level
   * 
   * @return array
   * 
   */
  public function convertRecordsToTree(array $records, int $idParent = 0, int $level = 0): array
  {
    $tree = [];
    foreach ($records as $record) {
      $recordId = (int) $record['id'];
      $recordIdParent = (int) $record['id_parent'];
      if ($recordIdParent == $idParent) {
        $tree[] = [
          'level' => $level,
          'id' => $recordId,
          'idParent' => $recordIdParent,
          'title' => $record['_LOOKUP'],
          'children' => $this->convertRecordsToTree($records, $recordId, $level + 1),
        ];
      }
    }
    return $tree;
  }

  /**
   * [Description for diffRecords]
   *
   * @param array $record1
   * @param array $record2
   * 
   * @return array
   * 
   */
  public function diffRecords(array $record1, array $record2): array
  {
    $diff = [];
    foreach ($this->getColumns() as $colName => $column) {
      $v1 = $record1[$colName] ?? null;
      $v2 = $record2[$colName] ?? null;
      if ($v1 != $v2) {
        $diff[$colName] = [ $v1, $v2 ];
      }
    }

    return $diff;

  }

  /**
   * [Description for getLookupSqlValue]
   *
   * @param string $tableAlias
   * 
   * @return string
   * 
   */
  public function getLookupSqlValue(string $tableAlias = ''): string
  {
    $value = $this->lookupSqlValue ?? "concat('{$this->fullName}, id = ', {%TABLE%}.id)";

    return ($tableAlias !== ''
      ? str_replace('{%TABLE%}', "`{$tableAlias}`", $value)
      : $value
    );
  }

  /**
   * [Description for getLookupValue]
   *
   * @param array $dataRaw
   * 
   * @return string
   * 
   */
  public function getLookupValue(array $dataRaw): string
  {
    return trim($dataRaw['_LOOKUP'] ?? '') ?? '[empty]';
  }

  /**
   * [Description for getLookupDetailValue]
   *
   * @param array $dataRaw
   * 
   * @return string
   * 
   */
  public function getLookupDetails(array $dataRaw): string
  {
    return '';
  }

  /**
   * [Description for getItemDetailUrl]
   *
   * @param int $id
   * 
   * @return string
   * 
   */
  public function getItemDetailUrl(int $id): string
  {
    $urlDetail = $this->lookupUrlDetail ?? '';
    if (!empty($urlDetail)) {
      return str_replace('{%ID%}', (string)$id, $urlDetail);
    } else {
      return '';
    }
  }

  /**
   * Returns maxReadLevel value used in loadTableData() method.
   * By default is set to 0 to save bandwidth when loading data.
   * Override this method in your model if you need to load more details.
   *
   * @return int
   * 
   */
  public function getMaxReadLevelForLoadTableData(): int
  {
    return 0;
  }

  /**
   * Returns list of relations to be included when loading table data.
   * By default, empty array is returned, which means no relations are included.
   * Override this method in your model if you need to specify particular relations.
   *
   * @return array
   * 
   */
  public function getRelationsIncludedInLoadTableData(): array|null
  {
    return null;
  }

  /**
   * Used to encrypt passowrd to store it securely.
   *
   * @param string $original
   * 
   * @return string
   * 
   */
  public function encryptPassword(string $original): string
  {
    return password_hash($original, PASSWORD_DEFAULT);
  }

  //////////////////////////////////////////////////////////////////
  // Callbacks

  /**
   * onBeforeCreate
   * @param array<string, mixed> $record
   * @return array<string, mixed>
   * @throws \Hubleto\Framework\Exceptions\RecordSaveException
   */
  public function onBeforeCreate(array $record): array
  {
    $this->eventManager()->fire('onModelBeforeCreate', [ $this, $record ]);
    return $record;
  }

  /**
   * onBeforeUpdate
   * @param array<string, mixed> $record
   * @return array<string, mixed>
   */
  public function onBeforeUpdate(array $record): array
  {
    $this->eventManager()->fire('onModelBeforeUpdate', [ $this, $record ]);
    return $record;
  }

  /**
   * onBeforeDelete
   * @param int $id
   * @return int
   */
  public function onBeforeDelete(int $id): int
  {
    $this->eventManager()->fire('onModelBeforeDelete', [ $this, $id ]);
    return $id;
  }

  /**
   * onAfterCreate
   * @param array<string, mixed> $originalRecord
   * @param array<string, mixed> $savedRecord
   * @return array<string, mixed>
   */
  public function onAfterCreate(array $savedRecord): array
  {
    $this->eventManager()->fire('onModelAfterCreate', [ $this, $savedRecord ]);
    return $savedRecord;
  }

  /**
   * onAfterUpdate
   * @param array<string, mixed> $originalRecord
   * @param array<string, mixed> $savedRecord
   * @return array<string, mixed>
   */
  public function onAfterUpdate(array $originalRecord, array $savedRecord): array
  {
    $this->eventManager()->fire('onModelAfterUpdate', [ $this, $originalRecord, $savedRecord ]);
    return $savedRecord;
  }

  /**
   * onAfterDelete
   * @param int $id
   * @return int
   */
  public function onAfterDelete(int $id): int
  {
    $this->eventManager()->fire('onModelAfterDelete', [ $this, $id ]);
    return $id;
  }

  /**
   * [Description for onAfterLoadRecord]
   *
   * @param array $record
   * 
   * @return array
   * 
   */
  public function onAfterLoadRecord(array $record): array
  {
    $this->eventManager()->fire('onModelAfterLoadRecord', [
      'model' => $this,
      'record' => $record
    ]);
    return $record;
  }

  /**
   * [Description for onAfterLoadRecords]
   *
   * @param array $records
   * 
   * @return array
   * 
   */
  public function onAfterLoadRecords(array $records): array
  {
    $this->eventManager()->fire('onModelAfterLoadRecords', [ $this, $records ]);
    return $records;
  }

}
