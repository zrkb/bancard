# Bancard

> A minimal implementation for Bancard API vPOS 2.0

<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/zrkb/bancard"><img src="https://poser.pugx.org/zrkb/bancard/license.svg" alt="License"></a>

## Installation

### Requirements

* PHP >= 7.0

### Installing

Run the following command in your console terminal:

```bash
$ composer require zrkb/bancard
```

### Usage

##### Single Buy

```php
use Bancard\Bancard;
use Bancard\Operations\SingleBuy;
use Bancard\Util\Currency;

Bancard::setPrivateKey('PRIVATE_KEY');
Bancard::setPublicKey('PUBLIC_KEY');

$response = SingleBuy::make([
    'shop_process_id' => 7777777,
    'amount' => '10000.00', // two decimals required
    'currency' => Currency::PYG,
    'return_url' => 'https://app.test/return_url',
    'cancel_url' => 'https://app.test/cancel_url',
]);

var_dump($response);
```

## Security

If you discover any security related issues, please use the issue tracker.

## Credits

- [Felix Ayala](http://felixaya.la)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
