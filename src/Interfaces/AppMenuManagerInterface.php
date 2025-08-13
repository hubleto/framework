<?php

namespace Hubleto\Framework\Interfaces;

interface AppMenuManagerInterface
{
  public \Hubleto\Framework\Loader $main { get; set; }

  /** @var array<int, array<string, bool|string>> */
  public array $items { get; set; }

  public function __construct(\HubletoMain\Loader $main);
  public function addItem(\Hubleto\Framework\Interfaces\AppInterface $app, string $url, string $title, string $icon): void;
  public function getItems(): array;

}
