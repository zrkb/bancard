# Changelog

## v2.0.0

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
