<?php declare(strict_types=1);

namespace Hubleto\Framework\Controllers\Api\Record;

class SaveJunction extends \Hubleto\Framework\Controllers\ApiController
{

  public function renderJson(): array
  {
    $junctionModel = $this->router()->urlParamAsString('junctionModel');
    $junctionSourceColumn = $this->router()->urlParamAsString('junctionSourceColumn');
    $junctionDestinationColumn = $this->router()->urlParamAsString('junctionDestinationColumn');
    $junctionSourceRecordId = $this->router()->urlParamAsInteger('junctionSourceRecordId');
    $junctionDestinationRecordId = $this->router()->urlParamAsInteger('junctionDestinationRecordId');

    $jModel = $this->getModel($junctionModel);

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
