<?php

namespace Hubleto\Framework\Controllers\Components\Inputs\Tags;

use Illuminate\Database\QueryException;

/**
 * @package Components\Controllers\Tags
 */
class Delete extends \Hubleto\Framework\Controller {
  public bool $hideDefaultDesktop = true;

  public function renderJson(): ?array { 
    try {
      $id = $this->getRouter()->urlParamAsInteger('id');
      $model = $this->getRouter()->urlParamAsString('model');
      $junction = $this->getRouter()->urlParamAsString('junction');

      // Validate required params
      if ($model == '') throw new \Exception("Unknown model");
      if ($junction == '') throw new \Exception("Unknown junction model");
      if ($id == 0) throw new \Exception("Unknown id");

      $tmpModel = $this->main->getModel($model);
      $junctionData = $tmpModel->junctions[$junction] ?? null;

      if ($junctionData == null) {
        throw new \Exception("Junction {$junction} in {$model} not found");
      }

      $junctionModel = $this->main->getModel($junctionData['junctionModel']);
      $junctionOptionKeyColumn = $junctionModel->getColumns()[$junctionData['optionKeyColumn']]->toArray();
      $junctionOptionKeyModel = $this->main->getModel($junctionOptionKeyColumn['model']);

      $junctionItemsToDelete = $junctionModel->record->where($junctionData['optionKeyColumn'], $id)
        ->get();

      foreach ($junctionItemsToDelete as $junctionItem) {
        $junctionModel->record->find($junctionItem->id)->recordDelete();
      }

      $junctionOptionKeyModel->record->find($id)->recordDelete();

      return [];
    } catch (QueryException $e) {
      http_response_code(500);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

}
