<?php

namespace Hubleto\Framework\Controllers;

use Hubleto\Framework\Description\Form;
use Hubleto\Framework\Description\Table;

class CrudController extends \Hubleto\Framework\Controller
{

  public function describeTable(): Table
  {
    $description = new Table;
    return $description;
  }

  public function describeForm(): Form
  {
    $description = new Form;
    return $description;
  }

  public function loadTableData(
    string $fulltextSearch = '',
    array $columnSearch = [],
    array $orderBy = [],
    int $itemsPerPage = 15,
    int $page = 0,
    string $dataView = ''
  ): array
  {
    return [
      'current_page' => 1,
      'from' => 0,
      'last_page' => 1,
      'per_page' => 35,
      'records' => [],
      'to' => 0,
      'total' => 0,
    ];
  }

}
