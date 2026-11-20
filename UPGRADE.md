# Upgrading from 1.0 to 1.1+

## PHP Version

1.1+ requires PHP 8.1 or later. If you need PHP 7.3+ support, use 1.0 (the `1.x` branch).

## Configuration

**Before (1.0):**

```php
use Bancard\Bancard;

Bancard::setPrivateKey('PRIVATE_KEY');
Bancard::setPublicKey('PUBLIC_KEY');
Bancard::setStaging(true);

$bancard = new Bancard;
```

**After (1.1+):**

```php
use Bancard\Bancard;

$bancard = new Bancard(
    publicKey: 'PUBLIC_KEY',
    privateKey: 'PRIVATE_KEY',
    staging: true,
);
```

## Operations

Operations are called the same way on the `$bancard` instance, but now return typed response objects instead of raw `stdClass`:

```php
// 1.0: returns stdClass
$response = $bancard->singleBuy([...]);
echo $response->status;

// 1.1+: returns SingleBuyResponse
$response = $bancard->singleBuy([...]);
echo $response->getStatus();
echo $response->isSuccessful(); // true/false
echo $response->getProcessId();
```

You can still access the raw response data via `$response->raw()`.

## SingleBuyConfirm

**Before (1.0):** `execute()` returns `false`, token accessed via `token()` method on the operation.

**After (1.1+):** Returns a `ConfirmTokenResponse` with `getToken()`:

```php
$response = $bancard->singleBuyConfirm([
    'shop_process_id' => '123',
    'amount' => '10000.00',
    'currency' => 'PYG',
]);

$token = $response->getToken();
```

## Currency

`Currency` is now a PHP 8.1 backed enum:

```php
// 1.0
use Bancard\Util\Currency;
$currency = Currency::PYG; // string 'PYG'

// 1.1+
use Bancard\Util\Currency;
$currency = Currency::PYG->value; // string 'PYG'
```

## Error Handling

1.1+ validates required fields before making API calls:

```php
use Bancard\Exception\ValidationException;

try {
    $response = $bancard->singleBuy([]);
} catch (ValidationException $e) {
    $e->getErrors(); // ['shop_process_id', 'amount', 'currency']
}
```

## Removed

- `Bancard::setPrivateKey()` / `Bancard::setPublicKey()` / `Bancard::setStaging()` -- use constructor named params
- `Bancard::privateKey()` / `Bancard::publicKey()` / `Bancard::staging()` -- use `$bancard->privateKey`, `$bancard->publicKey`, `$bancard->staging`
- `Operation::make()` static factory -- operations are created via the `Bancard` instance
- `zrkb/php-http-client` dependency -- HTTP is handled internally via Guzzle
- Guzzle 6 support -- only Guzzle 7 is supported
