<?php

declare(strict_types=1);

namespace Bancard\Exception;

/**
 * Thrown when a confirmation callback body is malformed or its token does not match.
 *
 * Respond with a non-200 status: the request did not come from Bancard
 * or was altered.
 */
class InvalidConfirmationException extends BancardException
{
}
