<?php
declare(strict_types=1);

namespace IdentityBridge\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Raised when a remote bearer token cannot be authenticated.
 */
class AuthenticationException extends CakeException
{
}
