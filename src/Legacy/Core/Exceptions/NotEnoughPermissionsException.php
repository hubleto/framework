<?php

namespace Hubleto\Legacy\Core\Exceptions;

/**
 * Thrown by the custom implementation of \Hubleto\Legacy\Core\checkPermissionsForAction() method.
 * Blocks rendering of the action's content.
 *
 * @package Exceptions
 */
class NotEnoughPermissionsException extends \Exception { }
