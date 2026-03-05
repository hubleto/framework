<?php

namespace Hubleto\Framework;

use Hubleto\Erp\Cli\Agent\App\Install;
use Hubleto\Framework\Enums\InstalledMigrationEnum;
use Hubleto\Framework\Exceptions\DBException;
use Hubleto\Framework\Interfaces\ModelInterface;
use Hubleto\Framework\Models\Migrations\DEPRECATED_25_02_2026_0001;

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

  public string $namespace = '';
  public string $srcFolder = '';
  public array $conversionRelations = [];
  public string $permission = '';
  public array $rolePermissions = []; // example: [ [UserRole::ROLE_CHIEF_OFFICER => [true, true, true, true]] ]

  public function __construct()
  {

    $reflection = new \ReflectionClass($this);

    $this->srcFolder = pathinfo((string) $reflection->getFilename(), PATHINFO_DIRNAME);
    $this->namespace = $reflection->getNamespaceName();

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
   * Returns the value of the sqlEngine property, which is used to specify the SQL engine for the model's table.
   *
   * @return array
   *
   */
  public function getSqlEngine(): string
  {
    return $this->sqlEngine;
  }

  // public function dropTableIfExists(): ModelInterface {
  //   foreach ($this->getSqlDropTableIfExists() as $sql) {
  //     $this->db()->execute($sql);
  //   }
  //   return $this;
  // }

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
   * Returns list of available migrations looked up from a folder. This method must be overridden by each model and
   * must include at least the default migration of a model.
   *
   * @return array List of available migrations
   * @throws \Exception
   */
  public function migrations(): array {
    $migrations = [];

    if (is_dir($this->srcFolder . '/Migrations')) {
      $mgrFiles = scandir($this->srcFolder . '/Migrations');
      foreach ($mgrFiles as $mgrFile) {
        if (str_starts_with($mgrFile, $this->shortName . '_')) {
          $mgrClass = $this->namespace . '\\Migrations\\' . str_replace('.php', '', $mgrFile);
          $migrations[] = new $mgrClass($this->db(), $this);
        }
      }
    }
    
    return $migrations;
    // return [
    //   0 => new DEPRECATED_25_02_2026_0001($this->db(), $this)
    // ];
  }

  /**
   * Looks for installed migrations in the database
   *
   * @return int
   *
   */
  private function getLatestInstalledMigration(InstalledMigrationEnum $configKey): int
  {
    $latestMigration = $this->config()->getAsInteger('models/' . str_replace("/", "-", $this->fullName) . '/' . $configKey->toString(), -1);

    // TODO: temporary backwards compatibility
    $latestUpgrade = $this->config()->getAsInteger('models/' . str_replace("/", "-", $this->fullName) . '/' .'installed-version', -1);
    if ($latestUpgrade == -1) $latestUpgrade = $this->config()->getAsInteger('models/' . str_replace("/", "-", $this->fullName) . '/' .'installed-upgrade', -1);

    if ($latestMigration == -1 && $latestUpgrade != -1) {
      $this->config()->save('models/' . str_replace("/", "-", $this->fullName) . '/' . InstalledMigrationEnum::TABLES->toString(), $latestUpgrade);
      $this->config()->save('models/' . str_replace("/", "-", $this->fullName) . '/' . InstalledMigrationEnum::FOREIGN_KEYS->toString(), $latestUpgrade);
      return $latestUpgrade;
    }

    return $latestMigration;
  }

  /**
   * Looks for the number of latest installed migration in the database
   *
   * @return int
   * 
   */
  private function getLatestMigration(): int
  {
    if (count($this->migrations()) == 0) {
      return -1;
    }
    return max(array_keys($this->migrations()));
  }

  /**
   * Retrieves migrations that are yet to be executed.
   *
   * @return array
   * 
   */
  public function getPendingMigrations(InstalledMigrationEnum $configKey): array
  {
    $availableUpgrades = [];
    $latestInstalledMigration = $this->getLatestInstalledMigration($configKey);
    $latestMigration = $this->getLatestMigration();

    $migrations = $this->migrations();

    for ($v = $latestInstalledMigration + 1; $v <= $latestMigration; $v++) {
      $availableUpgrades[] = $migrations[$v];
    }
    
    return $availableUpgrades;
  }

  /**
   * Installs tables of all pending migrations. Internally stores the latest installed migration.
   *
   * @return void
   * @throws DBException When an error occurred during the upgrade.
   */
  public function installTables(): void
  {
    $pendingMigrations = $this->getPendingMigrations(InstalledMigrationEnum::TABLES);

    if (count($pendingMigrations) > 0) {
      try {
        $this->db()->startTransaction();

        foreach ($pendingMigrations as $migration) {
          if ($migration instanceof Migration) {
            $migration->installTables();
          }
        }

        $this->db()->commit();
        $this->config()->save('models/' . str_replace("/", "-", $this->fullName) . '/' . InstalledMigrationEnum::TABLES->toString(), $this->getLatestMigration());
      } catch (DBException $e) {
        $this->db()->rollback();
        throw new DBException($e->getMessage());
      }
    }
  }

  /**
   * Installs indexes and foreign keys of all pending migrations. Internally stores the latest installed migration.
   *
   * @return void
   * @throws DBException When an error occurred during the upgrade.
   */
  public function installForeignKeys(): void
  {
    $pendingMigrations = $this->getPendingMigrations(InstalledMigrationEnum::FOREIGN_KEYS);

    if (count($pendingMigrations) > 0) {
      try {
        $this->db()->startTransaction();

        foreach ($pendingMigrations as $migration) {
          if ($migration instanceof  Migration) {
            $migration->installForeignKeys();
          }
        }

        $this->db()->commit();
        $this->config()->save('models/' . str_replace("/", "-", $this->fullName) . '/' . InstalledMigrationEnum::FOREIGN_KEYS->toString(), $this->getLatestMigration());
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
      'export-csv' => [ 'title' => $this->translate('Export to CSV', [], 'hubleto-erp-loader:Model'), 'type' => 'stateChange', 'state' => 'showExportCsvScreen', 'value' => true ],
      'import-csv' => [ 'title' => $this->translate('Import from CSV', [], 'hubleto-erp-loader:Model'), 'type' => 'stateChange', 'state' => 'showImportCsvScreen', 'value' => true ],
    ];

    if (!empty($tag)) {
      $description->ui['moreActions']['columns'] = [
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
   * Returns a tree description of the model.
   * The descriptions contains configuration for tree UI.
   *
   * @return \Hubleto\Framework\Description\Tree
   * 
   */
  public function describeTree(): \Hubleto\Framework\Description\Tree
  {
    $description = new \Hubleto\Framework\Description\Tree();
    $description->ui['title'] = '1';
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
          'CHILDREN' => $this->convertRecordsToTree($records, $recordId, $level + 1),
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
   * [Description for getRecordDetailUrl]
   *
   * @param int $id
   * 
   * @return string
   * 
   */
  public function getRecordDetailUrl(array $record): string
  {
    $urlDetail = $this->lookupUrlDetail ?? '';
    if (!empty($urlDetail)) {
      return str_replace('{%ID%}', (string) ($record['id'] ?? 0), $urlDetail);
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
