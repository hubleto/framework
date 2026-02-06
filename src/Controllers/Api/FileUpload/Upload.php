<?php

namespace Hubleto\Framework\Controllers\Components\Inputs\FileUpload;

/**
 * @package Components\Controllers\FileUpload
 */
class Upload extends \Hubleto\Framework\Controller
{
  public bool $hideDefaultDesktop = TRUE;

  public function renderJson(): array {
    try {
      $filesToUpload = $_FILES['upload'];

      $uploadedFiles = [];
      for ($i = 0; $i < count($filesToUpload['tmp_name']);$i++) {
        $uploadedFiles[] = $this->uploadFile($filesToUpload['name'][$i], $filesToUpload['tmp_name'][$i]);
      }

      return [
        'status' => 'success',
        'message' => 'The file has been successfully uploaded',
        'uploadedFiles' => $uploadedFiles
      ];
    } catch (\Exception $e) {
      http_response_code(400);

      return [
        'status' => 'error',
        'message' => $e->getMessage() 
      ];
    }
  }

  private function uploadFile(string $fileName, string $sourceFile): array {
    $renamePattern = $this->router()->urlParamAsString('renamePattern');

    if (!empty($renamePattern)) {
      $uploadedFileExtension = strtolower($fileName, PATHINFO_EXTENSION);
      $tmpParts = pathinfo($fileName);

      $fileName = $renamePattern;
      $fileName = str_replace("{%Y%}", date("Y"), $fileName);
      $fileName = str_replace("{%M%}", date("m"), $fileName);
      $fileName = str_replace("{%D%}", date("d"), $fileName);
      $fileName = str_replace("{%H%}", date("H"), $fileName);
      $fileName = str_replace("{%I%}", date("i"), $fileName);
      $fileName = str_replace("{%S%}", date("s"), $fileName);
      $fileName = str_replace("{%TS%}", strtotime("now"), $fileName);
      $fileName = str_replace("{%RAND%}", rand(1000, 9999), $fileName);
      $fileName = str_replace("{%BASENAME%}", $tmpParts['basename'], $fileName);
      $fileName = str_replace("{%BASENAME_ASCII%}", \Hubleto\Framework\Helper::str2url($tmpParts['basename']), $fileName);
      $fileName = str_replace("{%FILENAME%}", $tmpParts['filename'], $fileName);
      $fileName = str_replace("{%FILENAME_ASCII%}", \Hubleto\Framework\Helper::str2url($tmpParts['filename']), $fileName);
      $fileName = str_replace("{%EXT%}", $tmpParts['extension'], $fileName);
    }

    $folderPath = $this->router()->urlParamAsString('folderPath');

    if (strpos($folderPath, "..") !== FALSE) {
      $folderPath = "";
    }

    if (empty($folderPath)) $folderPath = ".";

    $uploadFolder = $this->config()->getAsString('uploadFolder');

    if (!is_dir("{$uploadFolder}/{$folderPath}")) {
      mkdir("{$uploadFolder}/{$folderPath}", 0775, TRUE);
    }

    $destinationFile = "{$uploadFolder}/{$folderPath}/{$fileName}";

    if (in_array($uploadedFileExtension, ['php', 'sh', 'exe', 'bat', 'htm', 'html', 'htaccess'])) {
      throw new \Exception('This file type cannot be uploaded');
    }
    // elseif (!empty($_FILES['upload']['error'])) {
    //
    //   $error = "File is too large. Maximum size of file to upload is ".round(ini_get('upload_max_filesize'), 2)." MB.";
    // } elseif (empty($_FILES['upload']['tmp_name']) || 'none' == $_FILES['upload']['tmp_name']) {
    //   $error = "Failed to upload the file for an unknown error. Try again in few minutes.";
    //   // } elseif (file_exists($destinationFile)) {
    //   //   $error = "File with this name is already uploaded.";
    // }
    //
    //

    if (is_file($destinationFile)) {
      throw new \Exception("The file already exists");
    }

    if (!move_uploaded_file($sourceFile, $destinationFile)) {
      throw new \Exception("An error occurred during the file upload");
    }

    return [
      'fullPath' => "{$folderPath}/{$fileName}",
    ];
  }
}
