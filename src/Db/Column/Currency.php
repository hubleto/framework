<?php

namespace Hubleto\Framework\Db\Column;

class Currency extends Decimal
{
  protected string $type = 'currency';
  protected string $textAlign = 'right';
}