<?php

namespace Hubleto\Framework\Models;

use Hubleto\Legacy\Core\Exceptions\GeneralException;

class Token extends \Hubleto\Legacy\Models\Token
{

  /**
   * @var \Illuminate\Database\Eloquent\Model
   */
  public object $record;

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'login' => new \Hubleto\Legacy\Core\Db\Column\Varchar($this, 'Login'),
    ]);
  }

  public function install(): void
  {
    parent::install();
    try {
      $this->registerTokenType('reset-password');
    } catch (GeneralException $e) {
      // no problem...
    }
  }
}
