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
);
```

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
use Bancard\Exception\ValidationException;

try {
    $response = $bancard->singleBuy($payload);
} catch (ValidationException $e) {
    // Missing required fields
    $errors = $e->getErrors(); // ['shop_process_id', 'amount', 'currency']
}
```

## Amount Formatting

```php
use Bancard\Util\Amount;

$formatted = Amount::format(10000); // "10000.00"
```

## Upgrading from v1

See [UPGRADE.md](UPGRADE.md) for the migration guide.

## Security

If you discover any security related issues, please use the issue tracker.

## Credits

- [Felix Ayala](http://felixaya.la)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
