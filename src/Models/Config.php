<?php

namespace Hubleto\Framework\Models;

class Config extends \Hubleto\Framework\Model
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

}
