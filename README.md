# Bancard

> A minimal implementation for Bancard API vPOS 2.0

<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/license.svg" alt="License"></a>

## Installation

### Requirements

* PHP >= 7.3

### Installing

Run the following command in your console terminal:

```bash
$ composer require zrkb/bancard
```

### Usage

#### Single Buy Request

```php
use Bancard\Bancard;
use Bancard\Util\Currency;

Bancard::setPrivateKey('PRIVATE_KEY');
Bancard::setPublicKey('PUBLIC_KEY');
Bancard::setStaging(true);

$bancard = new Bancard;

$response = $bancard->singleBuy([
    'shop_process_id' => 7777777, // MUST be an integer
    'name' => 'My Product',
    'description' => 'Product Description',
    'amount' => '10000.00', // two decimals required
    'currency' => Currency::PYG,
    'return_url' => 'https://app.test/return_url',
    'cancel_url' => 'https://app.test/cancel_url',
]);
```

The above example will return:

```json
{
    "status": "success",
    "process_id": "KKt*PMyY88Jv88Wjrk7-"
}
```

#### Single Buy Confirmation

```php
$response = $bancard->singleBuyConfirmation([
    'shop_process_id' => 7777777,
]);
```

#### Single Buy Rollback

```php
$response = $bancard->singleBuyRollback([
    'shop_process_id' => 7777777,
]);
```

## Security

If you discover any security related issues, please use the issue tracker.

## Credits

- [Felix Ayala](http://felixaya.la)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
