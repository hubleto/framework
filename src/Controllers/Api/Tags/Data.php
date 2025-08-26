<?php

namespace Hubleto\Framework\Controllers\Components\Inputs\Tags;

use Illuminate\Database\QueryException;

/**
 * @package Components\Controllers\Tags
 */
class Data extends \Hubleto\Framework\Controller {
  public bool $hideDefaultDesktop = true;

  function __construct(\Hubleto\Framework\Loader $main, array $params = []) {
    parent::__construct($main, $params);
  }

  public function renderJson(): ?array { 
    try {
      $id = $this->getRouter()->urlParamAsInteger('id');
      $model = $this->getRouter()->urlParamAsString('model');
      $junction = $this->getRouter()->urlParamAsString('junction');

      // Validate required params
      if ($model == '') throw new \Exception("Unknown model");
      if ($junction == '') throw new \Exception("Unknown junction model");

      $tmpModel = $this->getModel($model);

      $junctionData = $tmpModel->junctions[$junction] ?? null;
      if ($junctionData == null) {
        throw new \Exception("Junction {$junction} in {$model} not found");
      }

      $junctionModel = $this->getModel($junctionData['junctionModel']);

      if ($id > 0) {
        $selected = $junctionModel->record->where($junctionData['masterKeyColumn'], $id)
          ->pluck($junctionData['optionKeyColumn']);
      }

      $junctionOptionKeyColumn = $junctionModel->getColumns()[$junctionData['optionKeyColumn']]->toArray();

      $junctionOptionKeyModel = $this->getModel($junctionOptionKeyColumn['model']);
      $data = $junctionOptionKeyModel->all();

      return [
        'data' => $data,
        'selected' => $selected ?? []
      ];
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
