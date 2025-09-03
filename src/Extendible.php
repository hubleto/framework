<?php declare(strict_types=1);

namespace Hubleto\Framework;

/**
 * Manager for app's extendibles.
 */
class Extendible extends Core
{
  public App $app;
  public array $items = [];

  public function getItems(): array
  {
    return [];
  }

}
