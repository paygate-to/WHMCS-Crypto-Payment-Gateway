<?php
/**
 * PayGate.to crypto gateway — shared library.
 *
 * One small include shared by both gateways (individual-coin + multi mode) and
 * their callbacks. Replaces the old ~150 near-identical per-coin PHP files: the
 * accepted coin list and its icons are fetched live from the PayGate coin
 * info API instead of being hard-coded one file per coin.
 *
 * All helpers are namespaced with the pgc_ prefix to avoid collisions inside
 * WHMCS.
 */

if (!defined('PGC_API_BASE')) {
    define('PGC_API_BASE', 'https://api.paygate.to/crypto/');
}
if (!defined('PGC_COIN_INFO_URL')) {
    define('PGC_COIN_INFO_URL', PGC_API_BASE . 'info.php');
}
if (!defined('PGC_MULTI_WALLET_URL')) {
    define('PGC_MULTI_WALLET_URL', PGC_API_BASE . 'multi-hosted-wallet.php');
}
if (!defined('PGC_COINMAP_TTL')) {
    define('PGC_COINMAP_TTL', 3600); // seconds
}

/**
 * API base for a gateway's "API Domain" setting: your dedicated PayGate API
 * endpoint (e.g. api-yourshop-....paygate.to), or PGC_API_BASE when it is empty.
 */
function pgc_api_base($apiDomain = '')
{
    $host = rtrim(str_replace(array('https://', 'http://'), '', trim((string) $apiDomain)), '/');
    if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
        return PGC_API_BASE;
    }
    return 'https://' . $host . '/crypto/';
}

/* -------------------------------------------------------------------------
 * HTTP helpers (curl with file_get_contents fallback)
 * ---------------------------------------------------------------------- */

/**
 * GET a URL and return the raw body, or false on failure.
 */
function pgc_http_get($url, $timeout = 30)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'WHMCS-PayGate-Crypto/2.0',
        ));
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $code >= 200 && $code < 400) {
            return $body;
        }
        return false;
    }

    // Fallback for hosts without curl.
    $ctx  = stream_context_create(array('http' => array('timeout' => $timeout)));
    $body = @file_get_contents($url, false, $ctx);
    return $body === false ? false : $body;
}

/**
 * POST a JSON body to a URL and return the raw response, or false on failure.
 */
function pgc_http_post_json($url, $jsonBody, $timeout = 30)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_USERAGENT      => 'WHMCS-PayGate-Crypto/2.0',
        ));
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $code >= 200 && $code < 400) {
            return $body;
        }
        return false;
    }

    $ctx = stream_context_create(array('http' => array(
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $jsonBody,
        'timeout' => $timeout,
    )));
    $body = @file_get_contents($url, false, $ctx);
    return $body === false ? false : $body;
}

/**
 * GET + JSON decode. Returns array, or null on failure.
 */
function pgc_get_json($url, $timeout = 30)
{
    $body = pgc_http_get($url, $timeout);
    if ($body === false) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

/* -------------------------------------------------------------------------
 * Coin list
 * ---------------------------------------------------------------------- */

/**
 * Path to the local coin-map cache file.
 */
function pgc_coinmap_cache_file()
{
    return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'pgc_coinmap_cache.json';
}

/**
 * Fetch and flatten the PayGate coin list into a simple map.
 *
 * Returns an associative array keyed by the canonical coin id used throughout
 * the module (and echoed back by PayGate in the payment callback):
 *
 *   - Standalone coins (btc, bch, ltc, doge, eth, trx): id === ticker.
 *   - Chain tokens:                                      id === "{chain}_{ticker}".
 *
 * The API path for any coin is "id" with "_" replaced by "/"
 * (e.g. "erc20_usdt" -> "erc20/usdt", "btc" -> "btc").
 *
 * Each entry: array( id, ticker, chain, label, logo, path ).
 *
 * Cached in a temp file for PGC_COINMAP_TTL seconds. On fetch failure a stale
 * cache is returned so the storefront keeps working. Pass $force = true to
 * bypass the cache (used right after the admin saves settings).
 *
 * @param bool   $force
 * @param string $apiBase Optional API base from pgc_api_base(); default PGC_COIN_INFO_URL.
 * @return array
 */
function pgc_coin_map($force = false, $apiBase = '')
{
    $cacheFile = pgc_coinmap_cache_file();

    if (!$force && is_readable($cacheFile)) {
        $age = time() - (int) @filemtime($cacheFile);
        if ($age >= 0 && $age < PGC_COINMAP_TTL) {
            $cached = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }
    }

    $data = pgc_get_json(($apiBase !== '' && $apiBase !== PGC_API_BASE) ? $apiBase . 'info.php' : PGC_COIN_INFO_URL, 15);

    if (!is_array($data)) {
        // Fall back to any stale cache so checkout keeps working.
        $stale = is_readable($cacheFile) ? json_decode((string) @file_get_contents($cacheFile), true) : null;
        return is_array($stale) ? $stale : array();
    }

    $map = array();

    foreach ($data as $topKey => $entry) {
        if (!is_array($entry)) {
            continue;
        }

        if (isset($entry['ticker'])) {
            // Standalone coin (btc, bch, ltc, doge, eth, trx).
            $ticker = (string) $entry['ticker'];
            $id     = $ticker;
            $map[$id] = array(
                'id'     => $id,
                'ticker' => $ticker,
                'chain'  => '',
                'label'  => isset($entry['coin']) ? (string) $entry['coin'] : strtoupper($ticker),
                'logo'   => isset($entry['logo']) ? (string) $entry['logo'] : '',
                'path'   => $id,
            );
            continue;
        }

        // Otherwise it is a chain group whose children are coins.
        $chain = (string) $topKey;
        foreach ($entry as $child) {
            if (!is_array($child) || !isset($child['ticker'])) {
                continue;
            }
            $ticker = (string) $child['ticker'];
            $id     = $chain . '_' . $ticker;
            $map[$id] = array(
                'id'     => $id,
                'ticker' => $ticker,
                'chain'  => $chain,
                'label'  => isset($child['coin']) ? (string) $child['coin'] : strtoupper($ticker),
                'logo'   => isset($child['logo']) ? (string) $child['logo'] : '',
                'path'   => str_replace('_', '/', $id),
            );
        }
    }

    if (!empty($map)) {
        @file_put_contents($cacheFile, json_encode($map));
    }

    return $map;
}

/**
 * Human-friendly label for a coin entry, e.g. "USD Coin (ERC20)".
 */
function pgc_coin_label($coin)
{
    $label = isset($coin['label']) ? $coin['label'] : '';
    $chain = isset($coin['chain']) ? $coin['chain'] : '';
    if ($chain !== '') {
        return $label . ' (' . strtoupper($chain) . ')';
    }
    return $label;
}

/**
 * Map a coin id to the API path ("erc20_usdt" -> "erc20/usdt").
 */
function pgc_coin_path($coinId)
{
    return str_replace('_', '/', strtolower($coinId));
}

/**
 * Stable WHMCS setting key for a coin's payout wallet field.
 * e.g. "avax-c_usdc.e" -> "w_avax_c_usdc_e".
 */
function pgc_wallet_setting_key($coinId)
{
    return 'w_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($coinId));
}

/* -------------------------------------------------------------------------
 * Callback signature (wallet-agnostic, based on a per-invoice nonce)
 * ---------------------------------------------------------------------- */

function pgc_secret($nonce)
{
    return hash('sha256', 'paygate_salt_' . $nonce);
}

function pgc_sign($invoiceId, $nonce)
{
    return hash_hmac('sha256', (string) $invoiceId, pgc_secret($nonce));
}

function pgc_verify_sig($invoiceId, $nonce, $sig)
{
    if ($nonce === '' || $sig === '') {
        return false;
    }
    return hash_equals(pgc_sign($invoiceId, $nonce), (string) $sig);
}

function pgc_make_nonce()
{
    return bin2hex(function_exists('random_bytes') ? random_bytes(16) : pack('N*', mt_rand(), mt_rand(), mt_rand(), mt_rand()));
}

/* -------------------------------------------------------------------------
 * Per-invoice payment state
 *
 * Stored server-side in the mod_paygatecrypto table. Older versions kept it as
 * JSON in the invoice "notes" field, which WHMCS shows to the client on the
 * invoice, so the callback nonce was visible. That legacy JSON is still read
 * (and moved into the table, then removed from the notes) so invoices already in
 * progress keep working; every callback must also carry PayGate's signature.
 * ---------------------------------------------------------------------- */

function pgc_store_table()
{
    static $ready = false;
    if (!$ready) {
        try {
            if (!\WHMCS\Database\Capsule::schema()->hasTable('mod_paygatecrypto')) {
                \WHMCS\Database\Capsule::schema()->create('mod_paygatecrypto', function ($table) {
                    $table->integer('invoice_id')->unsigned()->primary();
                    $table->mediumText('payload');
                });
            }
        } catch (\Exception $e) {
            // Another request may have created the table at the same moment.
        }
        $ready = true;
    }
    return \WHMCS\Database\Capsule::table('mod_paygatecrypto');
}

function pgc_read_invoice_payload($invoiceId)
{
    $invoiceId = (int) $invoiceId;
    if ($invoiceId <= 0) {
        return null;
    }

    $row = pgc_store_table()->where('invoice_id', $invoiceId)->value('payload');
    if (is_string($row) && $row !== '') {
        $data = json_decode($row, true);
        return is_array($data) ? $data : null;
    }

    // Legacy: state saved in the invoice notes by an older version.
    $notes = \WHMCS\Database\Capsule::table('tblinvoices')->where('id', $invoiceId)->value('notes');
    if (!is_string($notes) || $notes === '') {
        return null;
    }
    $data = json_decode(html_entity_decode($notes), true);
    if (!is_array($data) || !isset($data['v'])) {
        return null;
    }
    pgc_write_invoice_payload($invoiceId, $data);
    \WHMCS\Database\Capsule::table('tblinvoices')->where('id', $invoiceId)->where('notes', $notes)->update(array('notes' => ''));
    return $data;
}

function pgc_write_invoice_payload($invoiceId, array $payload)
{
    $invoiceId = (int) $invoiceId;
    if ($invoiceId <= 0) {
        return false;
    }
    $json  = json_encode($payload);
    $table = pgc_store_table();
    if ($table->where('invoice_id', $invoiceId)->exists()) {
        pgc_store_table()->where('invoice_id', $invoiceId)->update(array('payload' => $json));
    } else {
        pgc_store_table()->insert(array('invoice_id' => $invoiceId, 'payload' => $json));
    }
    return true;
}

/* -------------------------------------------------------------------------
 * PayGate callback signature (RSA, SHA-256, PKCS#1 v1.5 over the exact URL)
 * https://paygate.to/docs/crypto-payment-api/#callback-event
 * ---------------------------------------------------------------------- */

if (!defined('PGC_KEYS_DIR')) {
    define('PGC_KEYS_DIR', __DIR__ . '/keys');
}

/**
 * Verify X-PayGate-Signature on the current request. $registeredBase is the
 * scheme, host and path of the callback URL the module registered (no query);
 * the signed string is that base plus the raw query string exactly as received.
 *
 * @return array verified (bool), reason (string)
 */
function pgc_verify_paygate_signature($registeredBase)
{
    $b64 = isset($_SERVER['HTTP_X_PAYGATE_SIGNATURE']) ? trim((string) $_SERVER['HTTP_X_PAYGATE_SIGNATURE']) : '';
    if ($b64 === '') {
        return array('verified' => false, 'reason' => 'signature_absent');
    }
    if (!function_exists('openssl_verify')) {
        return array('verified' => false, 'reason' => 'openssl_unavailable');
    }

    $keyId = isset($_SERVER['HTTP_X_PAYGATE_KEY_ID']) ? trim((string) $_SERVER['HTTP_X_PAYGATE_KEY_ID']) : '';
    if ($keyId === '') {
        $keyId = 'v1';
    }
    if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/', $keyId)) {
        return array('verified' => false, 'reason' => 'unknown_key_id');
    }
    $pemFile = PGC_KEYS_DIR . '/paygate_callback_public_' . $keyId . '.pem';
    $pem = is_readable($pemFile) ? file_get_contents($pemFile) : false;
    if (!is_string($pem) || $pem === '') {
        return array('verified' => false, 'reason' => 'unknown_key_id');
    }
    $publicKey = openssl_pkey_get_public($pem);
    if ($publicKey === false) {
        return array('verified' => false, 'reason' => 'bad_public_key');
    }

    $raw = base64_decode($b64, true);
    if ($raw === false || $raw === '') {
        return array('verified' => false, 'reason' => 'signature_not_base64');
    }

    $query  = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
    $signed = ($query === '') ? $registeredBase : $registeredBase . '?' . $query;

    $result = openssl_verify($signed, $raw, $publicKey, OPENSSL_ALGO_SHA256);
    if ($result === 1) {
        return array('verified' => true, 'reason' => '');
    }
    return array('verified' => false, 'reason' => ($result === -1) ? 'openssl_error' : 'signature_mismatch');
}

/**
 * Registered callback base URL for a gateway's callback file.
 */
function pgc_callback_base($gatewayModuleName)
{
    $systemUrl = '';
    if (isset($GLOBALS['CONFIG']['SystemURL'])) {
        $systemUrl = $GLOBALS['CONFIG']['SystemURL'];
    } elseif (class_exists('WHMCS\\Config\\Setting')) {
        $systemUrl = \WHMCS\Config\Setting::getValue('SystemURL');
    }
    return rtrim((string) $systemUrl, '/') . '/modules/gateways/callback/' . $gatewayModuleName . '.php';
}

/**
 * Stop the callback with an HTTP status and a short plain-text reason.
 */
function pgc_fail($status, $message)
{
    $texts = array(400 => 'Bad Request', 403 => 'Forbidden', 404 => 'Not Found', 409 => 'Conflict', 502 => 'Bad Gateway');
    header('HTTP/1.1 ' . (int) $status . ' ' . (isset($texts[$status]) ? $texts[$status] : 'Error'));
    header('Content-Type: text/plain');
    echo $message;
    exit;
}
