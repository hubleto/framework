<?php declare(strict_types=1);

namespace Hubleto\Framework\Controllers\Api\Record;

class SaveJunction extends \Hubleto\Framework\Controllers\ApiController
{

  public function renderJson(): array
  {
    $junctionModel = $this->main->urlParamAsString('junctionModel');
    $junctionSourceColumn = $this->main->urlParamAsString('junctionSourceColumn');
    $junctionDestinationColumn = $this->main->urlParamAsString('junctionDestinationColumn');
    $junctionSourceRecordId = $this->main->urlParamAsInteger('junctionSourceRecordId');
    $junctionDestinationRecordId = $this->main->urlParamAsInteger('junctionDestinationRecordId');

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
