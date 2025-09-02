<?php

namespace Hubleto\Framework\Controllers\Components\Inputs\Tags;

use Illuminate\Database\QueryException;

/**
 * @package Components\Controllers\Tags
 */
class Add extends \Hubleto\Framework\Controller {
  public bool $hideDefaultDesktop = true;

  public function renderJson(): ?array { 
    try {
      $id = $this->router()->urlParamAsInteger('id');
      $model = $this->router()->urlParamAsString('model');
      $junction = $this->router()->urlParamAsString('junction');
      $dataKey = $this->router()->urlParamAsString('dataKey');

      // Validate required params
      if ($model == '') throw new \Exception("Unknown model");
      if ($junction == '') throw new \Exception("Unknown junction model");
      if ($dataKey == '') throw new \Exception("Unknown  model");
      if ($id == 0) throw new \Exception("Unknown id");

      $tmpModel = $this->getModel($model);
      $junctionData = $tmpModel->junctions[$junction] ?? null;

      if ($junctionData == null) {
        throw new \Exception("Junction {$junction} in {$model} not found");
      }

      $junctionModel = $this->getModel($junctionData['junctionModel']);
      $junctionOptionKeyColumn = $junctionModel->getColumns()[$junctionData['optionKeyColumn']]->toArray();
      $junctionOptionKeyModel = $this->getModel($junctionOptionKeyColumn['model']);

      $insertedId = $junctionOptionKeyModel->insertGetId([
        $dataKey => $this->router()->urlParamAsString($dataKey)
      ]);

      // Junction table insert
      $junctionDataForInsert = [];
      $junctionDataForInsert[$junctionData['optionKeyColumn']] = $insertedId;
      $junctionDataForInsert[$junctionData['masterKeyColumn']] = $id;
      $junctionModel->insert($junctionDataForInsert);

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
