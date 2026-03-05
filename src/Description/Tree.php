<?php

namespace Hubleto\Framework\Description;


class Tree implements \JsonSerializable
{

  /** @property array{ title: string, subTitle: string, addButtonText: string, showHeader: bool, showFooter: bool, showFilter: bool, showHeaderTitle: bool } */
  public array $ui = [
    'title' => '',
    'showFulltextSearch' => true,
  ];

  /**
   * [Description for jsonSerialize]
   *
   * @return array
   * 
   */
  public function jsonSerialize(): array
  {
    $json = [];
    $json['ui'] = $this->ui;
    return $json;
  }

  /**
   * [Description for toArray]
   *
   * @return array
   * 
   */
  public function toArray(): array
  {
    return $this->jsonSerialize();
  }

  /**
   * [Description for show]
   *
   * @param array $what
   * 
   * @return void
   * 
   */
  public function show(array $what): void
  {
    foreach ($what as $item) {
      $item = 'show' . strtoupper(substr($item, 0, 1)) . substr($item, 1);
      if (isset($this->ui[$item])) $this->ui[$item] = true;
    }
  }

  /**
   * [Description for hide]
   *
   * @param array $what
   * 
   * @return void
   * 
   */
  public function hide(array $what): void
  {
    foreach ($what as $item) {
      $item = 'show' . strtoupper(substr($item, 0, 1)) . substr($item, 1);
      if (isset($this->ui[$item])) $this->ui[$item] = false;
    }
  }

}