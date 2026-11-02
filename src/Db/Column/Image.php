<?php

namespace Hubleto\Framework\Db\Column;

class Image extends \Hubleto\Framework\Db\Column\File
{

  protected string $type = 'image';

  // SVG is left out on purpose: an SVG file can contain script.
  private const EXTENSION_BY_IMAGE_TYPE = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
  ];

  public function normalize(mixed $value): mixed
  {

    if ($value['fileData']) {
      $fileContent = base64_decode(preg_replace('/data:.*?,/', '', $value['fileData']));
      $imageType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($fileContent);

      $isAllowedImage = isset(self::EXTENSION_BY_IMAGE_TYPE[$imageType]);
      if (!$isAllowedImage) {
        throw new \Exception($this->getTitle() . ': Only PNG, JPEG, GIF and WebP images can be uploaded.');
      }
    }

    return parent::normalize($value);
  }

}