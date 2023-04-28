# Bancard

> A minimal implementation for Bancard API vPOS 2.0

<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/license.svg" alt="License"></a>

## Installation

### Requirements

* PHP >= 7.3 | ^8.0

### Installing

```bash
composer require zrkb/bancard
```

## Configuration

```php
use Bancard\Bancard;

Bancard::setPrivateKey('PRIVATE_KEY');
Bancard::setPublicKey('PUBLIC_KEY');
Bancard::setStaging(true); // Use staging environment

$bancard = new Bancard;
```

## Usage

### Single Buy

```php
use Bancard\Util\Currency;

$response = $bancard->singleBuy([
    'shop_process_id' => 7777777,
    'name' => 'My Product',
    'description' => 'Product Description',
    'amount' => '10000.00',
    'currency' => Currency::PYG,
    'return_url' => 'https://app.test/return_url',
    'cancel_url' => 'https://app.test/cancel_url',
]);
```

### Single Buy Confirm

```php
$response = $bancard->singleBuyConfirm([
    'shop_process_id' => 7777777,
    'amount' => '10000.00',
    'currency' => Currency::PYG,
]);
```

### Single Buy Get Confirmation

```php
$response = $bancard->singleBuyGetConfirmation([
    'shop_process_id' => 7777777,
]);
```

### Single Buy Rollback

```php
$response = $bancard->singleBuyRollback([
    'shop_process_id' => 7777777,
]);
```

### Register a New Card

```php
$response = $bancard->cardsNew([
    'card_id' => 123,
    'user_id' => 456,
    'return_url' => 'https://app.test/return_url',
]);
```

### List User Cards

```php
$response = $bancard->usersCards([
    'user_id' => 456,
]);
```

### Charge (Token-Based Payment)

```php
$response = $bancard->charge([
    'shop_process_id' => 7777777,
    'amount' => '10000.00',
    'currency' => Currency::PYG,
    'alias_token' => 'card_alias_token',
]);
```

### Delete Card

```php
$response = $bancard->deleteCard([
    'user_id' => 456,
    'alias_token' => 'card_alias_token',
]);
```

### Preauthorization Confirm

```php
$response = $bancard->preauthorizationConfirm([
    'shop_process_id' => 7777777,
]);
```

### Billing Client Info

```php
$response = $bancard->billingClientInfo([
    // billing client info fields
]);
```

### Billing Cancel

```php
$response = $bancard->billingCancel([
    'shop_process_id' => 7777777,
]);
```

## Security

If you discover any security related issues, please use the issue tracker.

## Credits

- [Felix Ayala](http://felixaya.la)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
