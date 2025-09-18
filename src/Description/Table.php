<?php

namespace Hubleto\Framework\Description;


class Table implements \JsonSerializable
{

  /** @property array{ title: string, subTitle: string, addButtonText: string, showHeader: bool, showFooter: bool, showFilter: bool, showHeaderTitle: bool } */
  public array $ui = [
    'title' => '',
    'subTitle' => '',
    'addButtonText' => '',
    'showHeader' => true,
    'showFooter' => true,
    'showFilter' => true,
    'showSidebarFilter' => true,
    'showHeaderTitle' => true,
    'showFulltextSearch' => false,
    'showColumnSearch' => false,
    'showMoreActionsButton' => false,
    'showAddButton' => true,
  ];

  /** @property array{ canCreate: bool, canRead: bool, canUpdate: bool, canDelete: bool } */
  public array $permissions = [
    'canCreate' => false,
    'canRead' => false,
    'canUpdate' => false,
    'canDelete' => false,
  ];

  /** @property array<\Hubleto\Framework\Column> */
  public array $columns = [];

  /** @property array<\Hubleto\Framework\Db\Input> */
  public array $inputs = [];

  public function jsonSerialize(): array
  {
    $json = [];
    $json['ui'] = $this->ui;
    $json['permissions'] = $this->permissions;
    if (count($this->columns) > 0) $json['columns'] = $this->columns;
    if (count($this->inputs) > 0) $json['inputs'] = $this->inputs;
    return $json;
  }

  public function toArray(): array
  {
    return $this->jsonSerialize();
  }

  public function show(array $what): void
  {
    foreach ($what as $item) {
      $item = 'show' . strtoupper(substr($item, 0, 1)) . substr($item, 1);
      if (isset($this->ui[$item])) $this->ui[$item] = true;
    }
  }

  public function hide(array $what): void
  {
    foreach ($what as $item) {
      $item = 'show' . strtoupper(substr($item, 0, 1)) . substr($item, 1);
      if (isset($this->ui[$item])) $this->ui[$item] = false;
    }
  }

  public function showOnlyColumns(array $columnNames): void
  {
    $newColumns = [];
    foreach ($columnNames as $colName) {
      if (isset($this->columns[$colName])) {
        $newColumns[$colName] = $this->columns[$colName];
      }
    }
    $this->columns = $newColumns;
  }

}