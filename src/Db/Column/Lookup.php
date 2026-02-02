<?php

namespace Hubleto\Framework\Db\Column;

class Lookup extends \Hubleto\Framework\Column
{

  protected string $type = 'lookup';
  protected string $sqlDataType = 'int(8)';
  protected string $rawSqlDefinition = 'NULL default NULL';
  protected string $searchAlgorithm = 'lookup';

  protected bool $disableForeignKey = false;
  protected string $foreignKeyColumn = 'id';
  protected string $foreignKeyOnDelete = 'RESTRICT';
  protected string $foreignKeyOnUpdate = 'RESTRICT';

  public function __construct(\Hubleto\Framework\Model $model, string $title, string $lookupModel = '', string $foreignKeyBehaviour = 'RESTRICT')
  {
    parent::__construct($model, $title);
    $this->lookupModel = $lookupModel;
    $this->foreignKeyOnDelete = $foreignKeyBehaviour;
    $this->foreignKeyOnUpdate = $foreignKeyBehaviour;
  }

  public function setFkOnDelete(string $fkOnDelete): Lookup { $this->foreignKeyOnDelete = $fkOnDelete; return $this; }
  public function setFkOnUpdate(string $fkOnUpdate): Lookup { $this->foreignKeyOnUpdate = $fkOnUpdate; return $this; }

  public function describeInput(): \Hubleto\Framework\Description\Input
  {

    $lookupModelObj = $this->model->getModel($this->lookupModel);
    if (!empty($lookupModelObj->lookupUrlAdd)) {
      $this->setInputProp('urlAdd', $lookupModelObj->lookupUrlAdd);
    }

    return parent::describeInput();
  }

  public function jsonSerialize(): array
  {
    $column = parent::jsonSerialize();
    $column['model'] = $this->lookupModel;
    $column['foreignKeyOnDelete'] = $this->foreignKeyOnDelete;
    $column['foreignKeyOnUpdate'] = $this->foreignKeyOnUpdate;
    return $column;
  }

  public function isEmpty(mixed $value): bool
  {
    return (int) $value <= 0;
  }
  
  public function normalize(mixed $value): mixed
  {
    if ($value === 0) {
      return null;
    } if (is_numeric($value)) {
      return ((int) $value) <= 0 ? 0 : (int) $value;
    } else if ($value['_isNew_'] ?? false) {
      $lookupModel = $this->model->app->getModel($this->model->getColumns()[$colName]->getLookupModel());
      return $lookupModel->record->recordCreate($lookupModel->getNewRecordDataFromString($value['_LOOKUP'] ?? ''))->id;
    } else if ($value['_useMasterRecordId_'] ?? false) {
      return $value;
    } else if (empty($value)) {
      return null;
    } else return null;
  }

  public function sqlIndexString(string $table, string $columnName): string
  {
    return "index `{$columnName}` (`{$columnName}`)";
  }

}