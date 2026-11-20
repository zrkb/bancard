<?php

declare(strict_types=1);

namespace Bancard\Tests\Smoke;

use Bancard\Bancard;
use Bancard\Exception\ApiException;
use Bancard\Operations\CardsNew;
use Bancard\Operations\SingleBuy;
use Bancard\Operations\SingleBuyGetConfirmation;
use Bancard\Operations\SingleBuyRollback;
use Bancard\Operations\UsersCards;
use Bancard\Util\Token;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Smoke tests against the Bancard staging environment.
 *
 * Not part of the default suite. Run with:
 *   BANCARD_STAGING_PUBLIC_KEY=... BANCARD_STAGING_PRIVATE_KEY=... vendor/bin/phpunit --testsuite smoke
 *
 * Optional: BANCARD_STAGING_USER_ID (users_cards), plus BANCARD_STAGING_CARD_ID of a card
 * registered for that user (charge), BANCARD_STAGING_RETURN_URL, and
 * BANCARD_SMOKE_TEST_CLIENT=1 to send test_client so runs don't tick Bancard's test list.
 *
 * Every request/response is written to build/smoke/<run>/ with keys and tokens redacted,
 * so real response shapes can be compared with the SDK's assumptions.
 */
class StagingSmokeTest extends TestCase
{
    private const AMOUNT = '1000.00';

    private const REDACTED_KEYS = ['public_key', 'token', 'alias_token'];

    private static ?string $reportDir = null;

    private string $publicKey;

    private string $privateKey;

    private Bancard $bancard;

    /** @var array<int, array{request: RequestInterface, response: ResponseInterface|null, error: mixed, options: array<mixed>}> */
    private array $history = [];

    /**
     * Create a staging client from the environment, or skip when keys are missing.
     */
    protected function setUp(): void
    {
        $publicKey = self::env('BANCARD_STAGING_PUBLIC_KEY');
        $privateKey = self::env('BANCARD_STAGING_PRIVATE_KEY');

        if ($publicKey === null || $privateKey === null) {
            $this->markTestSkipped('Set BANCARD_STAGING_PUBLIC_KEY and BANCARD_STAGING_PRIVATE_KEY to run staging smoke tests.');
        }

        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->bancard = $this->makeClient($publicKey, $privateKey);
    }

    /**
     * Write every recorded exchange of the test to the report directory.
     */
    protected function tearDown(): void
    {
        foreach ($this->history as $i => $entry) {
            $this->writeReport(sprintf('%s-%02d', $this->name(), $i + 1), $entry);
        }
    }

    /**
     * Print where the report was written.
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$reportDir !== null) {
            fwrite(STDERR, PHP_EOL . 'Staging smoke report: ' . self::$reportDir . PHP_EOL);
        }
    }

    /**
     * single_buy returns a process_id; get_confirmation and rollback of the pending payment are recorded.
     */
    public function testSingleBuyLifecycle(): void
    {
        $shopProcessId = self::shopProcessId();

        $response = (new SingleBuy($this->bancard, [
            'shop_process_id' => $shopProcessId,
            'amount' => self::AMOUNT,
            'currency' => 'PYG',
            'description' => 'SDK smoke test',
            'return_url' => self::returnUrl(),
            'cancel_url' => self::returnUrl(),
        ] + self::extra()))->execute();

        $this->assertNotEmpty($response->getProcessId(), 'single_buy returned no process_id.');

        $this->attempt(fn () => (new SingleBuyGetConfirmation($this->bancard, ['shop_process_id' => $shopProcessId] + self::extra()))->execute());
        $this->attempt(fn () => (new SingleBuyRollback($this->bancard, ['shop_process_id' => $shopProcessId] + self::extra()))->execute());
    }

    /**
     * A wrong private key is reported as ApiException with an error key; the report shows the HTTP status used.
     */
    public function testInvalidTokenIsReportedAsApiException(): void
    {
        $wrongKey = $this->makeClient($this->publicKey, 'wrong-private-key');

        $result = $this->attempt(
            fn () => (new SingleBuyGetConfirmation($wrongKey, ['shop_process_id' => self::shopProcessId()] + self::extra()))->execute()
        );

        $this->assertInstanceOf(ApiException::class, $result);
        $this->assertNotNull($result->getErrorKey(), 'Error response had no messages[0].key.');
    }

    /**
     * Requests the SDK rejects locally are sent raw, to see whether Bancard enforces the same spec limits.
     */
    public function testSpecLimitsAgainstBancard(): void
    {
        $cases = [
            'description_21_chars' => ['shop_process_id' => self::shopProcessId(), 'description' => str_repeat('a', 21)],
            'non_numeric_shop_process_id' => ['shop_process_id' => 'smoke-' . self::shopProcessId(), 'description' => 'SDK smoke test'],
        ];

        foreach ($cases as $case => $fields) {
            $operation = $fields + [
                'amount' => self::AMOUNT,
                'currency' => 'PYG',
                'return_url' => self::returnUrl(),
                'cancel_url' => self::returnUrl(),
            ] + self::extra();
            $operation['token'] = Token::make($this->privateKey, $fields['shop_process_id'], self::AMOUNT, 'PYG');

            $raw = $this->bancard->request('POST', '/vpos/api/0.3/single_buy', [
                'public_key' => $this->publicKey,
                'operation' => $operation,
            ]);

            $this->assertContains($raw->status ?? null, ['success', 'error'], "{$case}: response had no recognised status.");

            if (($raw->status ?? null) === 'success') {
                $this->attempt(fn () => (new SingleBuyRollback($this->bancard, ['shop_process_id' => $fields['shop_process_id']] + self::extra()))->execute());
            }
        }
    }

    /**
     * cards_new returns a process_id for the card registration iframe.
     */
    public function testCardsNewReturnsProcessId(): void
    {
        $response = (new CardsNew($this->bancard, [
            'card_id' => self::shopProcessId(),
            'user_id' => self::env('BANCARD_STAGING_USER_ID') ?? self::shopProcessId(),
            'user_cell_phone' => '0981000000',
            'user_mail' => 'smoke@example.com',
            'return_url' => self::returnUrl(),
        ] + self::extra()))->execute();

        $this->assertNotEmpty($response->getProcessId(), 'cards_new returned no process_id.');
    }

    /**
     * users_cards lists the registered cards of the configured user.
     */
    public function testUsersCards(): void
    {
        $userId = $this->requireEnv('BANCARD_STAGING_USER_ID');

        $response = (new UsersCards($this->bancard, ['user_id' => $userId] + self::extra()))->execute();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * chargeCard() with and without the 3DS attribute; a successful charge must parse as approved or declined.
     */
    public function testChargeCardResponseShapes(): void
    {
        $userId = $this->requireEnv('BANCARD_STAGING_USER_ID');
        $cardId = $this->requireEnv('BANCARD_STAGING_CARD_ID');

        $cases = [
            'default' => [],
            'with_3ds_attribute' => ['extra_response_attributes' => ['confirmation.process_id']],
        ];

        foreach ($cases as $case => $fields) {
            $shopProcessId = self::shopProcessId();

            $result = $this->attempt(fn () => $this->bancard->chargeCard([
                'user_id' => $userId,
                'card_id' => $cardId,
                'shop_process_id' => $shopProcessId,
                'amount' => self::AMOUNT,
                'currency' => 'PYG',
            ] + $fields + self::extra()));

            if ($result instanceof ApiException) {
                $this->assertSame(
                    'error',
                    $result->getStatus(),
                    "{$case}: charge response has no 'status'; ChargeResponse's shape assumption is likely wrong. See " . self::reportDir()
                );
            } else {
                $this->assertNotNull(
                    $result->getResponseCode(),
                    "{$case}: successful charge has no confirmation.response_code; ChargeResponse's shape assumption is likely wrong. See " . self::reportDir()
                );
            }

            $this->attempt(fn () => (new SingleBuyRollback($this->bancard, ['shop_process_id' => $shopProcessId] + self::extra()))->execute());
        }
    }

    /**
     * Create a staging client that records every exchange in $history.
     *
     * @param string $publicKey Staging public key
     * @param string $privateKey Staging private key
     * @return Bancard
     */
    private function makeClient(string $publicKey, string $privateKey): Bancard
    {
        $bancard = new Bancard(publicKey: $publicKey, privateKey: $privateKey, staging: true);

        $stack = HandlerStack::create();
        $stack->push(Middleware::history($this->history)); // @phpstan-ignore assign.propertyType

        $bancard->setHttp(new Client([
            'handler' => $stack,
            'base_uri' => $bancard->baseUri(),
            'timeout' => Bancard::DEFAULT_TIMEOUT,
            'connect_timeout' => Bancard::DEFAULT_CONNECT_TIMEOUT,
        ]));

        return $bancard;
    }

    /**
     * Run an SDK call and return the ApiException instead of throwing, so the test can continue.
     *
     * @template T
     * @param callable(): T $call SDK call
     * @return T|ApiException
     */
    private function attempt(callable $call): mixed
    {
        try {
            return $call();
        } catch (ApiException $e) {
            return $e;
        }
    }

    /**
     * Write one recorded exchange as JSON, with keys and tokens redacted.
     *
     * @param string $name Report file name without extension
     * @param array{request: RequestInterface, response: ResponseInterface|null, error: mixed, options: array<mixed>} $entry Guzzle history entry
     */
    private function writeReport(string $name, array $entry): void
    {
        ['request' => $request, 'response' => $response, 'error' => $error] = $entry;

        $responseBody = $response instanceof ResponseInterface ? (string) $response->getBody() : null;

        $report = [
            'request' => [
                'method' => $request->getMethod(),
                'path' => $request->getUri()->getPath(),
                'body' => self::redact(json_decode((string) $request->getBody(), true)),
            ],
            'response' => $response instanceof ResponseInterface ? [
                'status' => $response->getStatusCode(),
                'body' => self::redact(json_decode((string) $responseBody, true) ?? $responseBody),
            ] : null,
            'error' => $error instanceof \Throwable ? get_class($error) . ': ' . $error->getMessage() : null,
        ];

        file_put_contents(
            self::reportDir() . "/{$name}.json",
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );
    }

    /**
     * Replace secret values (keys, tokens, alias tokens) recursively.
     *
     * @param mixed $value Decoded JSON value
     * @return mixed Value with secrets replaced by "[redacted]"
     */
    private static function redact(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = in_array($key, self::REDACTED_KEYS, true) && $item !== null ? '[redacted]' : self::redact($item);
        }

        return $value;
    }

    /**
     * Get (and create) the report directory for this run.
     *
     * @return string Absolute path under build/smoke/
     */
    private static function reportDir(): string
    {
        if (self::$reportDir === null) {
            self::$reportDir = dirname(__DIR__, 2) . '/build/smoke/' . date('Ymd-His');

            if (!is_dir(self::$reportDir)) {
                mkdir(self::$reportDir, 0775, true);
            }
        }

        return self::$reportDir;
    }

    /**
     * Generate a unique 15-digit shop_process_id (no leading zero).
     *
     * @return string
     */
    private static function shopProcessId(): string
    {
        return '1' . substr((string) (int) (microtime(true) * 1000), -11) . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the return/cancel URL sent to Bancard.
     *
     * @return string
     */
    private static function returnUrl(): string
    {
        return self::env('BANCARD_STAGING_RETURN_URL') ?? 'https://example.com/bancard/return';
    }

    /**
     * Get extra operation fields from the environment (test_client when BANCARD_SMOKE_TEST_CLIENT=1).
     *
     * @return array<string, mixed>
     */
    private static function extra(): array
    {
        return self::env('BANCARD_SMOKE_TEST_CLIENT') === '1' ? ['test_client' => true] : [];
    }

    /**
     * Read a non-empty environment variable.
     *
     * @param string $name Variable name
     * @return string|null Value, or null when unset or empty
     */
    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Read a required environment variable, or skip the test.
     *
     * @param string $name Variable name
     * @return string Value
     */
    private function requireEnv(string $name): string
    {
        $value = self::env($name);

        if ($value === null) {
            $this->markTestSkipped("Set {$name} to run this test.");
        }

        return $value;
    }
}
