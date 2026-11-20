# Changelog

## 1.7

Verified against Bancard staging; updated for vPOS spec 1.25.1.

### Behavior Changes

- **`charge()`/`chargeCard()` require `description`** (max 20 characters). Bancard rejects charges without it ("The parameter description is missing.") even though the spec's field table omits it, so charges without it never worked.
- **`charge()` sends `additional_data: ""` and `extra_response_attributes: ["confirmation.process_id"]` by default.** Bancard rejects charges without `additional_data` (marked optional in the spec), and the spec says to always request `confirmation.process_id` for 3DS. Pass `null` to omit either.
- **Required fields set to `""` count as missing**, as Bancard reports them.

### New

- **3DS:** `ChargeResponse::requires3ds()` and `get3dsProcessId()`. 3DS is required when `confirmation.process_id` is set; mount `Bancard.Charge3DS.createForm()` with it.
- **`ChargeResponse` confirmation getters:** `getShopProcessId()`, `getAuthorizationNumber()`, `getTicketNumber()`, `getResponseDescription()`, `getRiskIndex()` and more.
- **Extra attributes (spec 1.24):** `getExtraAttributes()`, `getPaymentCardType()`, `getPaymentCardBrand()`, `getPaymentMaskedCardNumber()` and `getPaymentOrigin()`, for `singleBuyGetConfirmation()` with `extra_response_attributes: ["confirmation.extra_attributes"]`.
- **`ConfirmationCallback::getCdc()`:** electronic invoice control code (spec 1.24).
- **`extra_response_attributes` in payload shapes:** for `singleBuyGetConfirmation()` and `usersCards()` (`cards.bancard_proccesed`).
- **`tests/Smoke/register-card.php`:** a page that registers a staging card for the smoke test.

### Deprecated

- **`ChargeResponse::getProcessId()`:** it returns the `shop_process_id`. Use `getShopProcessId()`; the 3DS process ID is `get3dsProcessId()`.
- **`ChargeResponse::is3dsRedirect()`:** use `requires3ds()`. It now also returns true when `confirmation.process_id` is set; it used to check only `redirect_agent`, which the spec does not define.

### Docs

- **README:** the 3DS flow and the card registration result (`?status=add_new_card_success|add_new_card_fail`).
- **Spec 1.23 to 1.25.1:** the 5-card limit per user, and QR/PIX in the payment form.
- **Errors:** `DuplicatePaymentError` (same card and amount within 5 minutes), `PaymentNotFoundError`, and the card blocking rule after repeated rejections.

## 1.6

### New

- **`Bancard::parseConfirmation()`**: parses and verifies the confirmation callback body, returning `Callback\ConfirmationCallback` with typed getters (response code and descriptions, authorization/ticket numbers, amount, security information, risk index, invoice number). Throws `InvalidConfirmationException` for malformed or wrongly signed bodies. `ConfirmationCallback::ACKNOWLEDGEMENT` is the `{"status":"success"}` answer Bancard expects.
- **`Bancard::checkoutScriptUrl()`**: URL of `bancard-checkout-4.0.0.js` for the configured environment (`Bancard::CHECKOUT_JS_VERSION`).
- **Confirmation getters on `ConfirmationResponse` and `PreauthorizationConfirmResponse`**: `getShopProcessId()`, `getAmount()`, `getCurrency()`, `getAuthorizationNumber()`, `getTicketNumber()`, `getResponseDescription()`, `getCardSource()`, `getRiskIndex()` and more (shared `ConfirmationDetails` trait). `PreauthorizationConfirmResponse` gains `getResponseCode()`.

### Internal

- `Response::stringValue()`/`objectValue()` moved to the `JsonValues` trait.

## 1.5

### Behavior Changes

- **Fields are validated against the vPOS spec**: `shop_process_id` (Entero 15; not for rollback/billing cancel), `card_id`/`user_id` (Entero 19), string lengths (`description` 20, `currency` 3, `client_ruc` 32, URLs/`additional_data`/`alias_token`/`user_cell_phone`/`user_mail` 255) and amounts (Decimal 15,2). Violations throw `ValidationException` listing every invalid field.
- **Network errors throw `NetworkException`**: timeouts, DNS and TLS failures are wrapped (Guzzle's exception is `getPrevious()`), so every SDK error extends `BancardException`.
- **`number_of_payments` defaults to 1** for `charge()`/`chargeCard()`, as the spec requires for debit cards. Pass `null` to omit it.
- **`billingClientInfo` requires `client_ruc`**: the spec's only request field (`client_name`/`client_email` were response fields, removed from the payload shape).

### Internal

- PHPDoc on every function in `src/` and `tests/`.
- Tests for the remaining convenience methods and `Response` message branches.
- `Operation` gains `defaults()`, `integerFields()` and `maxLengths()` hooks.

## 1.4

### Behavior Changes

- **`cardsNew` requires `user_cell_phone` and `user_mail`**: both are mandatory per the vPOS spec; missing values now throw `ValidationException` before the request.

### New

- **`Bancard::chargeCard()`**: charges a saved card by `user_id` + `card_id`, fetching a fresh alias token via `users_cards` first (alias tokens are single-use and expire within minutes). Throws `CardNotFoundException` when the card is not registered.
- **`UsersCardsResponse::findCard()`**: finds a card by `card_id` (string or int).
- `number_of_payments` and `return_url` documented in the `charge()` payload shape.

### Fixes

- `UsersCardsResponse::getCards()` skips non-object entries.

## 1.3

### Behavior Changes

- **HTTP error statuses no longer throw Guzzle exceptions**: 4xx/5xx responses with a Bancard JSON body now raise `ApiException` (with error key), instead of `GuzzleHttp\Exception\ClientException`/`ServerException`.
- **Non-JSON responses raise `BancardException`**: Empty, HTML or non-object bodies throw `BancardException` (code = HTTP status) instead of a `TypeError`.
- **Default timeouts**: `timeout` 30s and `connect_timeout` 10s (`Bancard::DEFAULT_TIMEOUT`, `Bancard::DEFAULT_CONNECT_TIMEOUT`). Override via the `guzzle` constructor option.
- **Amount validation**: `amount`/`iva_amount` values that are not non-negative numbers with at most two decimals throw `ValidationException`.
- **Null required fields**: a required field set to `null` now throws `ValidationException` (previously `InvalidArgumentException`).

### Fixes

- **Amounts are normalized**: ints, floats and numeric strings are sent and signed as `"0.00"`, fixing token mismatches for values like `10000`.
- **`Currency` enum accepted in payloads**: backed enums are converted to their value (previously threw `Error`).
- **Path parameters are URL-encoded**: `user_id` in `/users/{user_id}/cards` can no longer alter the request path (e.g. `../`).
- **Private key redacted**: hidden from `var_dump()`/`print_r()` (`__debugInfo()`) and stack traces (`#[\SensitiveParameter]`).
- **Response getters tolerate numeric JSON**: IDs/codes sent as numbers are returned as strings; non-object `confirmation`/`client` return `null` (previously `TypeError`).

### New

- `Bancard::verifyConfirmation()` verifies the confirmation callback token with `hash_equals()`.
- `Bancard::setHttp()` accepts any `GuzzleHttp\ClientInterface`.
- `Amount::normalize()` returns the formatted amount, or `null` if invalid.
- `ValidationException` accepts an optional custom message.
- PHPDoc on all `src/` methods.

## 1.2

- **Guzzle 8 support**: `guzzlehttp/guzzle` constraint widened to `^7.0 || ^8.0`.

## 1.1

### Breaking Changes

- **PHP 8.1+ required** (dropped PHP 7.3/7.4/8.0 support)
- **Instance-based configuration**: `Bancard` constructor uses named parameters (`publicKey`, `privateKey`, `staging`) instead of static methods. `Bancard::setPrivateKey()`, `Bancard::setPublicKey()`, and `Bancard::setStaging()` are removed.
- **Removed `zrkb/php-http-client` dependency**: HTTP logic is inlined using Guzzle directly.
- **Removed static `Operation::make()` factory**: Operations now require a `Bancard` client instance as the first constructor argument.
- **`Currency` is now a backed enum**: Use `Currency::PYG->value` instead of `Currency::PYG`.
- **`Token::make()` uses variadic `string ...$parts`** instead of `func_get_args()`.
- **All methods have strict return types**: Operations return typed Response objects instead of raw `stdClass`.
- **`SingleBuyConfirm::execute()`** returns `ConfirmTokenResponse` instead of `false`.
- **Guzzle 6 dropped**: Only Guzzle 7 is supported.

### New Features

- **Response objects**: All operations return typed response objects (`SingleBuyResponse`, `ChargeResponse`, etc.) with convenience methods like `isSuccessful()`, `isApproved()`, `getProcessId()`, `getCards()`, `is3dsRedirect()`.
- **Input validation**: Operations validate required fields before executing. Throws `ValidationException` with a list of missing fields.
- **Exception hierarchy**: `BancardException` base class and `ValidationException` for structured error handling.
- **`Amount` utility**: `Amount::format()` for consistent amount string formatting.
- **Zimple support**: `$bancard->singleBuyZimple()` convenience method.
- **`setHttp()` method**: Inject a custom Guzzle client for testing or custom HTTP configuration.

### Bug Fixes

- **Fixed `array_filter` data loss bug**: `array_filter()` in `Operation::data()` no longer strips `0`, `false`, and `''` values. Only `null` values are removed.

### Internal

- `declare(strict_types=1)` on all files
- PHPStan level 8 with zero errors (upgraded to PHPStan 2.x)
- PHPUnit 10+/11 (upgraded from PHPUnit 9)
- Comprehensive test suite with 125 tests and JSON fixtures
- Template generics on `Operation` for type-safe response inference
