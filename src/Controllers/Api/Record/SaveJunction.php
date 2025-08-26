<?php declare(strict_types=1);

namespace Hubleto\Framework\Controllers\Api\Record;

class SaveJunction extends \Hubleto\Framework\Controllers\ApiController
{

  public function renderJson(): array
  {
    $junctionModel = $this->getRouter()->urlParamAsString('junctionModel');
    $junctionSourceColumn = $this->getRouter()->urlParamAsString('junctionSourceColumn');
    $junctionDestinationColumn = $this->getRouter()->urlParamAsString('junctionDestinationColumn');
    $junctionSourceRecordId = $this->getRouter()->urlParamAsInteger('junctionSourceRecordId');
    $junctionDestinationRecordId = $this->getRouter()->urlParamAsInteger('junctionDestinationRecordId');

    $jModel = $this->main->load($junctionModel);

    $tmp = $jModel
      ->record
      ->where($junctionSourceColumn, $junctionSourceRecordId)
      ->where($junctionDestinationColumn, $junctionDestinationRecordId)
      ->get()?->toArray()
    ;

    if (is_array($tmp) && count($tmp) == 0) {
      $jModel->record->recordCreate([
        $junctionSourceColumn => $junctionSourceRecordId,
        $junctionDestinationColumn => $junctionDestinationRecordId,
      ]);
    }

    return [];
  }
}
