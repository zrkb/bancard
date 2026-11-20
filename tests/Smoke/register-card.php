<?php

/**
 * Staging card registration page for the smoke test.
 *
 * Calls cards_new, mounts Bancard's card form, and after Bancard redirects
 * back checks users_cards and shows the card_id to use as
 * BANCARD_STAGING_CARD_ID.
 *
 * Keys come from the environment or, when unset, from .env.smoke in the
 * project root (KEY=value lines). Serve it with Valet
 * (http://bancard.test/tests/Smoke/register-card.php) or with
 * php -S localhost:8000 tests/Smoke/register-card.php, then register a
 * staging test card (cédula 9661000).
 */

declare(strict_types=1);

use Bancard\Bancard;
use Bancard\Exception\ApiException;
use Bancard\Exception\BancardException;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Escape a value for HTML output.
 *
 * @param string|null $value Value to escape
 * @return string
 */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Load KEY=value lines into the environment, keeping variables that are already set.
 *
 * Supports blank lines, # comments, an optional "export " prefix and quoted values.
 *
 * @param string $path Env file path
 */
function loadEnvFile(string $path): void
{
    $lines = is_readable($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : false;

    foreach ($lines ?: [] as $line) {
        if (!preg_match('/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*?)\s*$/', $line, $match)) {
            continue;
        }

        $value = preg_replace('/^([\'"])(.*)\1$/', '$2', $match[2]) ?? $match[2];

        if (getenv($match[1]) === false) {
            putenv($match[1] . '=' . $value);
        }
    }
}

/**
 * Read a non-empty environment variable.
 *
 * @param string $name Variable name
 * @return string|null
 */
function env(string $name): ?string
{
    $value = getenv($name);

    return is_string($value) && $value !== '' ? $value : null;
}

/**
 * Render the page and stop.
 *
 * @param string $title Page heading
 * @param string $body HTML body (already escaped)
 */
function render(string $title, string $body): never
{
    echo '<!doctype html><html><head><meta charset="utf-8"><title>' . h($title) . '</title>'
        . '<style>body{font:15px/1.5 system-ui,sans-serif;max-width:760px;margin:40px auto;padding:0 16px}'
        . 'code,pre{background:#f3f3f3;padding:2px 6px;border-radius:4px}pre{padding:12px;overflow:auto}'
        . '#iframe-container{min-height:420px;border:1px solid #ddd;border-radius:8px}</style></head><body>'
        . '<h1>' . h($title) . '</h1>' . $body . '</body></html>';
    exit;
}

/**
 * Describe a Bancard error for the page.
 *
 * @param BancardException $e Exception thrown by the SDK
 * @return string HTML
 */
function errorHtml(BancardException $e): string
{
    $key = $e instanceof ApiException ? ' (' . h($e->getErrorKey()) . ')' : '';

    return '<p><strong>Bancard error' . $key . ':</strong> ' . h($e->getMessage()) . '</p>';
}

if (PHP_SAPI === 'cli-server' && parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) === '/favicon.ico') {
    http_response_code(404);
    exit;
}

loadEnvFile(__DIR__ . '/../../.env.smoke');

$publicKey = env('BANCARD_STAGING_PUBLIC_KEY');
$privateKey = env('BANCARD_STAGING_PRIVATE_KEY');
$userId = env('BANCARD_STAGING_USER_ID');

if ($publicKey === null || $privateKey === null || $userId === null) {
    render('Missing configuration', '<p>Set <code>BANCARD_STAGING_PUBLIC_KEY</code>, <code>BANCARD_STAGING_PRIVATE_KEY</code>'
        . ' and <code>BANCARD_STAGING_USER_ID</code> in <code>.env.smoke</code> (project root) or the environment.</p>');
}

$bancard = new Bancard(publicKey: $publicKey, privateKey: $privateKey, staging: true);
$testClient = env('BANCARD_SMOKE_TEST_CLIENT') === '1' ? ['test_client' => true] : [];
$returnedCardId = isset($_GET['card_id']) && is_string($_GET['card_id']) ? $_GET['card_id'] : null;

// Back from Bancard: confirm the card is listed for the user.
if ($returnedCardId !== null) {
    $query = $_GET;
    unset($query['card_id']);
    $sent = $query === [] ? '' : '<p>Bancard sent back:</p><pre>' . h((string) json_encode($query, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';

    try {
        $card = $bancard->usersCards(['user_id' => $userId] + $testClient)->findCard($returnedCardId);
    } catch (BancardException $e) {
        render('Could not check the card', $sent . errorHtml($e));
    }

    if ($card === null) {
        render('Card not registered', $sent . '<p>users_cards has no card <code>' . h($returnedCardId) . '</code> for user <code>'
            . h($userId) . '</code>. <a href="?">Try again</a>.</p>');
    }

    render('Card registered', $sent
        . '<p>Card <code>' . h($returnedCardId) . '</code> (' . h(is_string($card->card_masked_number ?? null) ? $card->card_masked_number : '') . ')'
        . ' is registered for user <code>' . h($userId) . '</code>. Add this line to <code>.env.smoke</code>:</p>'
        . '<pre>BANCARD_STAGING_CARD_ID=' . h($returnedCardId) . '</pre>'
        . '<p>Then run the smoke test.</p>');
}

// Start a registration: cards_new, then mount the iframe with its process_id.
$cardId = (string) (int) (microtime(true) * 1000);
$host = is_string($_SERVER['HTTP_HOST'] ?? null) ? $_SERVER['HTTP_HOST'] : 'localhost:8000';
$https = is_string($_SERVER['HTTPS'] ?? null) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$selfUrl = ($https ? 'https' : 'http') . '://' . $host . (is_string($path) && $path !== '' ? $path : '/');

try {
    $processId = $bancard->cardsNew([
        'card_id' => $cardId,
        'user_id' => $userId,
        'user_cell_phone' => '0981000000',
        'user_mail' => 'smoke-test@example.com',
        'return_url' => $selfUrl . '?card_id=' . $cardId,
    ] + $testClient)->getProcessId();
} catch (BancardException $e) {
    render('cards_new failed', errorHtml($e));
}

if ($processId === null) {
    render('cards_new failed', '<p>Bancard returned no process_id.</p>');
}

render('Register a staging test card', '<p>User <code>' . h($userId) . '</code>, card <code>' . h($cardId) . '</code>.'
    . ' Enter a staging test card; use cédula <code>9661000</code> when asked.'
    . ' Bancard redirects back here when done. If the form does not load in Safari, use Chrome.</p>'
    . '<div id="iframe-container"></div>'
    . '<script src="' . h($bancard->checkoutScriptUrl()) . '"></script>'
    . '<script>window.onload = function () { Bancard.Cards.createForm("iframe-container", '
    . json_encode($processId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ', {}); };</script>');
