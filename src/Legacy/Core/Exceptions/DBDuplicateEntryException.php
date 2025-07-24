<?php

namespace Hubleto\Legacy\Core\Exceptions;

/**
 * Thrown when a database query execution is blocked by any foreign key constraint.
 *
 * @package Exceptions
 */
class DBDuplicateEntryException extends \Exception { }
