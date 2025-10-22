<?php

namespace Hubleto\Framework\Exceptions;

/**
 * Thrown by the custom implementation of \Hubleto\Framework\checkPermissionsForAction() method.
 * Blocks rendering of the action's content.
 *
 * @package Exceptions
 */
class NotEnoughPermissionsException extends Exception { }
