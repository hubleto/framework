<?php

namespace Hubleto\Framework\Controllers\Components\Inputs\FileUpload;

/**
 * @package Components\Controllers\FileUpload
 */
class Delete extends \Hubleto\Framework\Controller {
  public bool $hideDefaultDesktop = TRUE;

  public function renderJson(): ?array {
    try {
      $fileFullPath =
        $this->config()->getAsString('uploadFolder')
        . '/' . $this->router()->urlParamAsString('fileFullPath')
      ;

      if (is_file($fileFullPath)) {
        if (!unlink($fileFullPath)) throw new \Exception("The deletion of the file encountered an error");
      } else {
        throw new \Exception("File not found");
      }

      return [
        'status' => 'success',
        'message' => 'The file has been successfully deleted'
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
