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
 * @param bool $force
 * @return array
 */
function pgc_coin_map($force = false)
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

    $data = pgc_get_json(PGC_COIN_INFO_URL, 15);

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
 * Invoice "notes" payload (we store the per-invoice payment state as JSON in
 * the invoice notes field, exactly like the original module did).
 * ---------------------------------------------------------------------- */

function pgc_read_invoice_payload($invoiceId)
{
    $invoice = localAPI('GetInvoice', array('invoiceid' => $invoiceId));
    if (!isset($invoice['result']) || $invoice['result'] !== 'success') {
        return null;
    }
    $notes = isset($invoice['notes']) ? html_entity_decode($invoice['notes']) : '';
    $data  = json_decode($notes, true);
    return is_array($data) ? $data : null;
}

function pgc_write_invoice_payload($invoiceId, array $payload)
{
    $invoice = localAPI('GetInvoice', array('invoiceid' => $invoiceId));
    if (!isset($invoice['result']) || $invoice['result'] !== 'success') {
        return false;
    }
    $invoice['notes'] = json_encode($payload);
    localAPI('UpdateInvoice', $invoice);
    return true;
}
