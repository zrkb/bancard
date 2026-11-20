<?php

declare(strict_types=1);

namespace Bancard\Exception;

/**
 * Thrown when the request to Bancard fails at the transport level (timeout, DNS, TLS, connection reset).
 *
 * The outcome of a payment request is unknown when this happens: the charge may
 * have been processed. Check it with singleBuyGetConfirmation() before retrying.
 */
class NetworkException extends BancardException
{
}
