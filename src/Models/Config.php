<?php

namespace Hubleto\Framework\Models;

use Hubleto\Framework\Model;

class Config extends Model
{

  public string $table = 'config';
  public string $recordManagerClass = RecordManagers\Config::class;

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'path' => new \Hubleto\Framework\Db\Column\Varchar($this, 'Path'),
      'value' => new \Hubleto\Framework\Db\Column\Text($this, 'Value'),
    ]);
  }

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      "path" => [
        "type" => "unique",
        "columns" => [
          "path" => [
            "order" => "asc",
          ],
        ],
      ],
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = 'Add config';
    $description->show(['header', 'fulltextSearch', 'columnSearch']);
    $description->hide(['footer']);
    return $description;
  }

}
