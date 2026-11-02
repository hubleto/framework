<?php

namespace Hubleto\Framework\Db\Column;

class File extends \Hubleto\Framework\Column
{

  protected string $type = 'file';
  protected string $sqlDataType = 'varchar(255)';
  protected string $folderPath = '';
  protected string $renamePattern = '';
  protected string $searchAlgorithm = 'text';

  public function getFolderPath(): string { return $this->folderPath; }
  public function setFolderPath(string $folderPath): File { $this->folderPath = $folderPath; return $this; }

  public function getRenamePattern(): string { return $this->renamePattern; }
  public function setRenamePattern(string $renamePattern): File { $this->renamePattern = $renamePattern; return $this; }

  public function normalize(mixed $value): mixed
  {
    $changed = false;

    if ($value === null) {
      return null;
    } else if ($value === '') {
      return '';
    } else if (is_string($value)) {
      $changed = false;
      $fileName = $value;
      $fileDataToSave = null;
    } else if (is_array($value)) {
      $changed = (bool) ($value['changed'] ?? false);
      $fileName = (string) ($value['fileName'] ?? '');
      $fileData = (string) ($value['fileData'] ?? '');
      $fileDataToSave = preg_replace('/data:.*?,/', '', $fileData);
      $fileDataToSave = @base64_decode($fileDataToSave);
    } else {
      throw new \Exception("Invalid format for file/image input.");
    }

    if (!$changed) return $fileName;

    $folderPath = $this->getFolderPath();
    $title = $this->getTitle();
    $renamePattern = $this->getRenamePattern();

    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (empty($this->model->config()->getAsString('uploadFolder'))) throw new \Exception("{$title}: Upload folder is not configured.");
    if (!is_dir($this->model->config()->getAsString('uploadFolder'))) throw new \Exception("{$title}: Upload folder does not exist.");
    if (in_array($fileExtension, ['php', 'sh', 'exe', 'bat', 'htm', 'html', 'htaccess'])) {
      throw new \Exception("{$title}: This file type cannot be uploaded.");
    }

    if (strpos($folderPath, "..") !== false) throw new \Exception("{$title}: Invalid upload folder path.");

    if (empty($renamePattern)) {
      $tmpParts = pathinfo($fileName);
      $fileName = \Hubleto\Framework\Helper::str2url($tmpParts['filename']) . '.' . $tmpParts['extension'];
    } else {
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


    if (empty($folderPath)) $folderPath = ".";

    $uploadFolder = $this->model->config()->getAsString('uploadFolder');

    if (!is_dir("{$uploadFolder}/{$folderPath}")) {
      mkdir("{$uploadFolder}/{$folderPath}", 0775, TRUE);
    }

    $destinationFile = "{$uploadFolder}/{$folderPath}/{$fileName}";

    if ($fileDataToSave !== null) {
      \file_put_contents($destinationFile, $fileDataToSave);
    }

    return "{$folderPath}/{$fileName}";

  }
}