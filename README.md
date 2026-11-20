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

### Payment Form (bancard-checkout.js)

`singleBuy()`, `singleBuyZimple()` and `cardsNew()` return a `process_id`. Bancard's iframe collects the card data with it:

```php
<div id="iframe-container"></div>
<script src="<?= $bancard->checkoutScriptUrl() ?>"></script>
<script>
    Bancard.Checkout.createForm('iframe-container', <?= json_encode($processId) ?>, {});
</script>
```

Use `Bancard.Zimple.createForm` for Zimple, `Bancard.Cards.createForm` for card registration and `Bancard.Charge3DS.createForm` for [3DS](#charge-token-based-payment). The URL follows the `staging` flag (`bancard-checkout-4.0.0.js`, `Bancard::CHECKOUT_JS_VERSION`).

The payment form also offers QR and PIX when they are enabled in the application profile of the commerce portal. They need no extra integration: the result arrives at the same confirmation callback.

### Confirmation Callback

Bancard POSTs the payment result to your confirmation URL. `parseConfirmation()` verifies its token (constant-time comparison) and returns a typed object:

```php
use Bancard\Callback\ConfirmationCallback;
use Bancard\Exception\InvalidConfirmationException;

try {
    $confirmation = $bancard->parseConfirmation((string) file_get_contents('php://input'));
} catch (InvalidConfirmationException $e) {
    http_response_code(400); // tampered, unsigned or malformed
    exit;
}

if ($confirmation->isApproved()) {
    $confirmation->getShopProcessId();
    $confirmation->getAuthorizationNumber();
    $confirmation->getRiskIndex(); // 1-3 low, 4-6 medium, 7-9 high, 0 not available
} else {
    $confirmation->getResponseDescription(); // e.g. "Fondos insuficientes"
}

header('Content-Type: application/json');
echo json_encode(ConfirmationCallback::ACKNOWLEDGEMENT); // {"status":"success"}
```

In Laravel: `$bancard->parseConfirmation($request->getContent())` and `return response()->json(ConfirmationCallback::ACKNOWLEDGEMENT);`.

With electronic invoicing (`billing`), `getInvoiceNumber()` and `getCdc()` return the generated invoice number and control code.

Bancard expects HTTP 200 within 30 seconds. If it does not get one, the payment is not necessarily denied: check it with `singleBuyGetConfirmation()`.

To check an already decoded `operation` array, use `$bancard->verifyConfirmation($operation)` (returns `bool`).

### Single Buy Get Confirmation

```php
$response = $bancard->singleBuyGetConfirmation([
    'shop_process_id' => '7777777',
    'extra_response_attributes' => ['confirmation.extra_attributes'], // optional
]);

if ($response->isApproved()) {
    $ticket = $response->getTicketNumber();
    $type = $response->getPaymentCardType(); // "credit", "debit" or "account_debit"
    $response->getPaymentCardBrand();         // e.g. "MC"
    $response->getPaymentMaskedCardNumber();  // e.g. "541863******0014"
    $response->getPaymentOrigin();            // e.g. "VPOS-FORM", "VPOS-TOKEN"
}
```

While the customer has not paid yet, this throws `ApiException` with key `PaymentNotFoundError`.

`singleBuyGetConfirmation()`, `preauthorizationConfirm()` and `charge()` responses have the same getters as the confirmation callback (`getResponseCode()`, `getAuthorizationNumber()`, `getTicketNumber()`, `getCardSource()`, `getRiskIndex()`, ...).

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
    'user_cell_phone' => '0981123456',
    'user_mail' => 'user@example.com',
    'return_url' => 'https://app.test/return',
]);

$processId = $response->getProcessId();
```

Mount `Bancard.Cards.createForm` with the `process_id` ([Payment Form](#payment-form-bancard-checkoutjs)). Bancard then redirects to `return_url` with `?status=add_new_card_success`, or `?status=add_new_card_fail&description=...`. Check the result with `usersCards()`. A user can have at most 5 registered cards. On staging, use cédula `9661000`.

### List User Cards

```php
$response = $bancard->usersCards([
    'user_id' => '456',
    'extra_response_attributes' => ['cards.bancard_proccesed'], // optional: adds bancard_proccesed (true/false) per card
]);

foreach ($response->getCards() as $card) {
    echo $card->card_masked_number; // also card_id, card_brand, card_type, expiration_date, alias_token
}
```

### Charge (Token-Based Payment)

```php
$response = $bancard->charge([
    'shop_process_id' => '7777777',
    'amount' => '10000.00',
    'currency' => Currency::PYG->value,
    'alias_token' => 'card_alias_token',
    'description' => 'Order 7777777', // required by Bancard, max 20 chars
]);

if ($response->requires3ds()) {
    // Mount Bancard.Charge3DS.createForm('iframe-container', $response->get3dsProcessId(), {});
    // the result arrives at the confirmation callback.
} elseif ($response->isApproved()) {
    $response->getAuthorizationNumber();
} else {
    $response->getResponseDescription();
}
```

`charge()` sends `number_of_payments: 1`, `additional_data: ""` and `extra_response_attributes: ["confirmation.process_id"]` (which enables 3DS detection) unless you pass other values. Pass `null` to omit one.

### Charge a Saved Card (Recurring Payments)

Alias tokens are single-use and expire within minutes, so don't store them. Store `user_id` + `card_id` and use `chargeCard()`: it fetches a fresh alias token with `usersCards()` and then charges.

```php
use Bancard\Exception\CardNotFoundException;

try {
    $response = $bancard->chargeCard([
        'user_id' => '456',
        'card_id' => '123',
        'shop_process_id' => '7777778',
        'amount' => 10000,
        'currency' => Currency::PYG,
        'description' => 'Monthly plan',
    ]);
} catch (CardNotFoundException $e) {
    // Card was deleted or never registered for this user
}
```

To pick the alias token yourself, use `$bancard->usersCards([...])->findCard($cardId)`.

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
    'client_ruc' => '123456-7',
]);

$client = $response->getClient(); // ->name, ->email
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
use Bancard\Exception\NetworkException;
use Bancard\Exception\ValidationException;

try {
    $response = $bancard->singleBuy($payload);
} catch (ValidationException $e) {
    // Missing required field, invalid amount, or a field over the spec's limits
    $errors = $e->getErrors(); // ['shop_process_id', 'description']
} catch (ApiException $e) {
    // Bancard returned a non-success status (any HTTP status code)
    $key = $e->getErrorKey(); // e.g. 'InvalidOperationError'
} catch (NetworkException $e) {
    // Timeout, DNS or TLS failure; $e->getPrevious() is Guzzle's exception
} catch (BancardException $e) {
    // Response was not valid JSON (e.g. gateway error page); $e->getCode() is the HTTP status
}
```

Every exception extends `BancardException`, so one `catch (BancardException $e)` covers them all.

After a `NetworkException` on a payment the outcome is unknown: Bancard may have processed it. Check with `singleBuyGetConfirmation()` before retrying with the same `shop_process_id`.

Error keys worth handling:

| Key | Meaning |
|---|---|
| `DuplicatePaymentError` | Same card and amount charged in the last 5 minutes. |
| `PaymentNotFoundError` | The customer has not paid this `shop_process_id` yet. |
| `BuyNotFoundError` | No payment with that `shop_process_id`. |
| `InvalidOperationError` | A field is missing or invalid (see `getMessage()`). |

Bancard blocks a card at the commerce for 30 days after 7 rejected payments in 24 hours or 35 in 30 days ("MANDATO MC MAC 03 y 21"). Don't retry rejected payments automatically.

### Field Validation

Fields are checked against the vPOS spec before sending, so mistakes fail locally with the field names instead of as `InvalidOperationError`:

- Integers: `shop_process_id` (max 15 digits; rollback and billing cancel accept any string), `card_id` and `user_id` (max 19 digits)
- Strings: `description` (20 chars), `currency` (3), `client_ruc` (32), URLs, `additional_data`, `alias_token`, `user_cell_phone` and `user_mail` (255)
- Amounts: at most 13 integer digits and 2 decimals
- Required fields set to `null` or `""` count as missing. `charge()` and `chargeCard()` require `description`: the spec omits it, but Bancard rejects charges without it.

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

## Upgrading from 1.0

See [UPGRADE.md](UPGRADE.md) for the migration guide.

## Development

```bash
composer install
vendor/bin/phpstan analyse
vendor/bin/phpunit            # unit tests (mocked HTTP)
```

### Staging Smoke Test

`tests/Smoke` runs real requests against Bancard staging. It is not part of the default suite or CI, and skips itself without credentials:

```bash
BANCARD_STAGING_PUBLIC_KEY=... \
BANCARD_STAGING_PRIVATE_KEY=... \
vendor/bin/phpunit --testsuite smoke
```

| Variable | Purpose |
|---|---|
| `BANCARD_STAGING_PUBLIC_KEY`, `BANCARD_STAGING_PRIVATE_KEY` | Required. Staging keys from the Bancard commerce portal. |
| `BANCARD_STAGING_USER_ID` | Runs `users_cards`, and is used for `cards_new`. |
| `BANCARD_STAGING_CARD_ID` | `card_id` of a card registered for that user. Runs `chargeCard()` (with and without the 3DS attribute) and `singleBuyGetConfirmation()` with extra attributes, then rolls back. |
| `BANCARD_STAGING_RETURN_URL` | Return/cancel URL sent to Bancard (default `https://example.com/bancard/return`). |
| `BANCARD_SMOKE_TEST_CLIENT=1` | Sends `test_client` so the runs don't count toward Bancard's staging test list. |

Keep the variables in `.env.smoke` (gitignored) and load them with `set -a; . ./.env.smoke; set +a`.

To get a `BANCARD_STAGING_CARD_ID`, serve `tests/Smoke/register-card.php`, for example with `php -S localhost:8000 tests/Smoke/register-card.php` or through Valet. It reads `.env.smoke`, opens Bancard's card form, and after registration shows the `card_id` to use. On staging, the card `5418630110000014` (expiry `08/21`, CVV `258`) with cédula `9661000` works.

Each request and response is written to `build/smoke/<run>/` with keys, tokens and alias tokens redacted, so real response shapes can be compared with what the SDK expects.

## Security

If you discover any security related issues, please use the issue tracker.

## Credits

- [Felix Ayala](http://felixaya.la)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
