# Bancard

> PHP SDK for the Bancard vPOS 2.0 payment gateway API

<a href="https://github.com/zrkb/bancard/actions"><img src="https://github.com/zrkb/bancard/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/license.svg" alt="License"></a>

## Installation

**Requirements:** PHP >= 8.1

```bash
composer require zrkb/bancard
```

## Configuration

```php
use Bancard\Bancard;

$bancard = new Bancard(
    publicKey: 'YOUR_PUBLIC_KEY',
    privateKey: 'YOUR_PRIVATE_KEY',
    staging: true, // optional, defaults to false
    guzzle: ['timeout' => 15], // optional Guzzle options
);
```

Requests time out after 30 seconds (10 seconds to connect) by default. Override with the `timeout` and `connect_timeout` Guzzle options.

The private key is hidden from `var_dump()`/`print_r()` and stack traces.

## Usage

All operations return typed response objects with convenience methods.

### Single Buy

```php
use Bancard\Util\Currency;

$response = $bancard->singleBuy([
    'shop_process_id' => '7777777',
    'amount' => '10000.00',
    'currency' => Currency::PYG->value,
    'return_url' => 'https://app.test/return',
    'cancel_url' => 'https://app.test/cancel',
]);

if ($response->isSuccessful()) {
    $processId = $response->getProcessId();
}
```

### Single Buy (Zimple)

```php
$response = $bancard->singleBuyZimple([
    'shop_process_id' => '7777777',
    'amount' => '10000.00',
    'currency' => Currency::PYG->value,
    'return_url' => 'https://app.test/return',
    'cancel_url' => 'https://app.test/cancel',
]);
```

### Single Buy Confirm

Generates the confirm token for the iframe callback (no HTTP request is made).

```php
$response = $bancard->singleBuyConfirm([
    'shop_process_id' => '7777777',
    'amount' => '10000.00',
    'currency' => Currency::PYG->value,
]);

$token = $response->getToken();
```

### Verify Confirmation Callback

Bancard POSTs the payment result to your confirmation URL. Verify its token before trusting it (constant-time comparison; returns `false` for tampered or malformed input):

```php
$body = json_decode((string) file_get_contents('php://input'), true);
$operation = is_array($body['operation'] ?? null) ? $body['operation'] : [];

if (!$bancard->verifyConfirmation($operation)) {
    http_response_code(400);
    exit;
}

// Trusted: $operation['response_code'], $operation['shop_process_id'], ...

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
```

Bancard expects HTTP 200 within 30 seconds. If it does not get one, the payment is not necessarily denied: check it with `singleBuyGetConfirmation()`.

### Single Buy Get Confirmation

```php
$response = $bancard->singleBuyGetConfirmation([
    'shop_process_id' => '7777777',
]);

if ($response->isApproved()) {
    $code = $response->getResponseCode();
}
```

### Single Buy Rollback

```php
$response = $bancard->singleBuyRollback([
    'shop_process_id' => '7777777',
]);
```

### Register a New Card

```php
$response = $bancard->cardsNew([
    'card_id' => '123',
    'user_id' => '456',
    'return_url' => 'https://app.test/return',
]);

$processId = $response->getProcessId();
```

### List User Cards

```php
$response = $bancard->usersCards([
    'user_id' => '456',
]);

foreach ($response->getCards() as $card) {
    echo $card->card_masked_number;
}
```

### Charge (Token-Based Payment)

```php
$response = $bancard->charge([
    'shop_process_id' => '7777777',
    'amount' => '10000.00',
    'currency' => Currency::PYG->value,
    'alias_token' => 'card_alias_token',
]);

if ($response->isApproved()) {
    // Payment successful
}

if ($response->is3dsRedirect()) {
    // 3DS authentication required
}
```

### Delete Card

```php
$response = $bancard->deleteCard([
    'user_id' => '456',
    'alias_token' => 'card_alias_token',
]);
```

### Preauthorization Confirm

```php
$response = $bancard->preauthorizationConfirm([
    'shop_process_id' => '7777777',
]);

if ($response->isApproved()) {
    // Preauthorization confirmed
}
```

### Billing Client Info

```php
$response = $bancard->billingClientInfo([
    // billing client info fields
]);

$client = $response->getClient();
```

### Billing Cancel

```php
$response = $bancard->billingCancel([
    'shop_process_id' => '7777777',
]);
```

## Response Objects

All responses extend `Bancard\Response\Response` and provide:

- `isSuccessful(): bool` -- checks if `status === 'success'`
- `getStatus(): ?string`
- `getMessage(): ?string` -- first message description
- `getErrorKey(): ?string` -- error key from messages
- `raw(): \stdClass` -- access the raw response data

Specialized responses add operation-specific methods (e.g., `getProcessId()`, `isApproved()`, `getCards()`).

## Error Handling

```php
use Bancard\Exception\ApiException;
use Bancard\Exception\BancardException;
use Bancard\Exception\ValidationException;

try {
    $response = $bancard->singleBuy($payload);
} catch (ValidationException $e) {
    // Missing required fields or invalid amount
    $errors = $e->getErrors(); // ['shop_process_id', 'amount', 'currency']
} catch (ApiException $e) {
    // Bancard returned a non-success status (any HTTP status code)
    $key = $e->getErrorKey(); // e.g. 'InvalidOperationError'
} catch (BancardException $e) {
    // Response was not valid JSON (e.g. gateway error page); $e->getCode() is the HTTP status
}
```

Network errors (timeouts, DNS, TLS) still throw `GuzzleHttp\Exception\GuzzleException`.

## Amounts and Currency

`amount` and `iva_amount` accept ints, floats or numeric strings and are sent and signed as `"10000.00"`. Strings with more than two decimals, separators like `"10,000"` or negative values throw `ValidationException`.

`currency` accepts the `Currency` enum or its string value:

```php
use Bancard\Util\Currency;

$bancard->singleBuy([
    'shop_process_id' => '7777777',
    'amount' => 10000,             // sent as "10000.00"
    'currency' => Currency::PYG,   // sent as "PYG"
]);
```

```php
use Bancard\Util\Amount;

$formatted = Amount::format(10000);      // "10000.00"
$normalized = Amount::normalize('10.5'); // "10.50" (null if invalid)
```

## Upgrading from v1

See [UPGRADE.md](UPGRADE.md) for the migration guide.

## Security

If you discover any security related issues, please use the issue tracker.

## Credits

- [Felix Ayala](http://felixaya.la)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
