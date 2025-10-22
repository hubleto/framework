<?php

namespace Hubleto\Framework\Exceptions;

/**
 * Used to display warning to the user if any problem with saving a form using Components/Form
 * action occurs. Thrown by model's recordValidate() method.
 *
 * @package Exceptions
 */
class RecordSaveException extends Exception {

  public const int CODE = 87335;
  public array $invalidInputs {
    get {
      return $this->invalidInputs;
    }
  }

  public function __construct(string $message, array $invalidInputs = [], $previous = null) {
    parent::__construct($message, static::CODE, $previous);
    $this->invalidInputs = $invalidInputs;
  }

  public function getExtraParams(): array
  {
    $extraParams = parent::getExtraParams();

    $extraParams['invalidInputs'] = $this->invalidInputs;

    return $extraParams;
  }

}
