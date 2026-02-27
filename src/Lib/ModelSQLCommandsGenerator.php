<?php

namespace Hubleto\Framework\Lib;

use Hubleto\Framework\Core;
use Hubleto\Framework\Interfaces\ModelInterface;

class ModelSQLCommandsGenerator extends Core
{
  /**
   * [Description for getSqlCreateTableCommands]
   *
   * @return array
   *
   */
  public function getSqlCreateTableCommands(ModelInterface $model): array
  {

    $columns = $model->getColumns();

    $createSql = "create table `{$model->getFullTableSqlName()}` (\n";

    foreach ($columns as $columnName => $column) {
      $tmp = $column->sqlCreateString($model->getFullTableSqlName(), $columnName);
      if (!empty($tmp)) $createSql .= " {$tmp},\n";

    }

    // indexy
    foreach ($columns as $columnName => $column) {
      $tmp = $column->sqlIndexString($model->getFullTableSqlName(), $columnName);
      if (!empty($tmp)) $createSql .= " {$tmp},\n";
    }

    $createSql = substr($createSql, 0, -2) . ") ENGINE = {$model->getSqlEngine()}";

    $commands = [];
    $commands[] = "SET foreign_key_checks = 0";
    $commands[] = "drop table if exists `{$model->getFullTableSqlName()}`";
    $commands[] = $createSql;
    $commands[] = "SET foreign_key_checks = 1";

    return $commands;

  }

  /**
   * Returns SQL commands for index creation
   *
   * @return array
   *
   */
  public function getSqlCreateIndexesCommands(ModelInterface $model): array
  {
    $commands = [];

    foreach ($model->indexes() as $indexOrConstraintName => $indexDef) {
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
          $commands[] ="
              alter table `" . $model->getFullTableSqlName() . "`
              add index `{$indexOrConstraintName}` ({$tmpColumns})
            ";
          break;
        case "unique":
          $commands[] = "
              alter table `" . $model->getFullTableSqlName() . "`
              add constraint `{$indexOrConstraintName}` unique ({$tmpColumns})
            ";
          break;
      }
    }

    return $commands;
  }

  /**
   * Get SQL commands for foreign key creation.
   *
   * @return void
   */
  public function getSqlCreateForeignKeysCommands(ModelInterface $model): array
  {
    $sql = '';
    foreach ($model->getColumns() as $colName => $column) {
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
          ALTER TABLE `{$model->getFullTableSqlName()}`
          ADD CONSTRAINT `fk_" . md5($model->getFullTableSqlName() . '_' . $colName) . "`
          FOREIGN KEY (`{$colName}`)
          REFERENCES `" . $lookupModel->getFullTableSqlName() . "` (`{$foreignKeyColumn}`)
          ON DELETE {$foreignKeyOnDelete}
          ON UPDATE {$foreignKeyOnUpdate};;
        ";
      }
    }

    $commands = [];
    if (!empty($sql)) {
      foreach (explode(';;', $sql) as $query) {
        $commands[] = trim($query);
      }
    }

    return $commands;
  }

  /**
   * Get SQL commands for foreign key removal.
   *
   * @return array
   */
  public function getSqlDropForeignKeysCommands(ModelInterface $model): array
  {
    $sql = '';
    foreach ($model->getColumns() as $colName => $column) {
      $columnDefinition = $column->toArray();

      if (
        !($columnDefinition['disableForeignKey'] ?? false)
        && 'lookup' == $columnDefinition['type']
      ) {
        $foreignKeyName = "fk_" . md5($model->getFullTableSqlName() . '_' . $colName);

        $sql .= "
          ALTER TABLE `{$model->getFullTableSqlName()}`
          DROP FOREIGN KEY `{$foreignKeyName}`;;
        ";
      }
    }

    $commands = [];
    if (!empty($sql)) {
      foreach (explode(';;', $sql) as $query) {
        $commands[] = trim($query);
      }
    }

    return $commands;
  }
}