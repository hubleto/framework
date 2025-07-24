<?php

namespace Hubleto\Legacy\Models;

class Config extends \Hubleto\Legacy\Core\Model
{

  public string $table = 'config';
  public string $recordManagerClass = RecordManagers\Config::class;

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'path' => new \Hubleto\Legacy\Core\Db\Column\Varchar($this, 'Path'),
      'value' => new \Hubleto\Legacy\Core\Db\Column\Text($this, 'Value'),
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

}
