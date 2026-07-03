<?php
/**
 * PayGate.to — Dynamic Individual-Coin Crypto Payment Gateway for WHMCS.
 *
 * One module that replaces the previous ~150 per-coin gateway files. The list
 * of accepted coins (and their icons) is fetched live from PayGate. The admin
 * enables a coin simply by entering a payout wallet address for it on the
 * gateway settings screen; coins left blank are not offered at checkout.
 *
 * On the invoice the customer picks one of the enabled coins, a unique pay-in
 * address + QR code is generated on your own site, and the server-to-server
 * callback (modules/gateways/callback/paygatecrypto.php) confirms payment by
 * valuing the received coin in the invoice currency.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/paygatecrypto/lib.php';

/* -------------------------------------------------------------------------
 * Module metadata / config
 * ---------------------------------------------------------------------- */

function paygatecrypto_MetaData()
{
    return array(
        'DisplayName'                 => 'Crypto Payment Gateway (Individual Coins)',
        'APIVersion'                  => '1.1',
        'DisableLocalCreditCardInput' => true,
    );
}

/**
 * Gateway settings. The global fields come first, then ONE payout-wallet text
 * field per coin is generated dynamically from the live PayGate coin list,
 * with the coin's icon shown beside it. Fill a wallet to accept that coin.
 */
function paygatecrypto_config()
{
    $fields = array(
        'FriendlyName' => array(
            'Type'  => 'System',
            'Value' => 'Crypto Payment Gateway — Individual Coins (No KYC, instant payouts)',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type'         => 'textarea',
            'Rows'         => '3',
            'Cols'         => '40',
            'Default'      => 'Pay with your preferred cryptocurrency.',
            'Description'  => 'Shown to the customer on the invoice above the coin selector.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type'         => 'yesno',
            'Description'  => 'Add estimated blockchain fees to the amount the customer must send.',
            'Default'      => 'off',
        ),
        'underpaid_tolerance' => array(
            'FriendlyName' => 'Underpaid Tolerance',
            'Type'         => 'dropdown',
            'Options'      => array(
                '1' => '0%', '0.99' => '1%', '0.98' => '2%', '0.97' => '3%', '0.96' => '4%',
                '0.95' => '5%', '0.94' => '6%', '0.93' => '7%', '0.92' => '8%', '0.91' => '9%',
                '0.90' => '10%',
            ),
            'Description'  => 'Tolerate underpayment when a customer sends slightly less than required (crypto rates are volatile; 1%+ recommended).',
            'Default'      => '0.99',
        ),
    );

    // Dynamic per-coin payout wallet fields.
    $map = pgc_coin_map();

    if (empty($map)) {
        $fields['coinlist_error'] = array(
            'FriendlyName' => 'Coin list',
            'Type'         => 'text',
            'Description'  => 'Could not fetch the coin list from PayGate right now. Save and reload this page, or check your server\'s outbound HTTPS connection to api.paygate.to.',
        );
        return $fields;
    }

    foreach ($map as $coinId => $coin) {
        $key  = pgc_wallet_setting_key($coinId);
        $icon = !empty($coin['logo'])
            ? '<img src="' . htmlspecialchars($coin['logo'], ENT_QUOTES) . '" alt="" style="width:18px;height:18px;vertical-align:middle;margin-right:6px;border-radius:50%;">'
            : '';
        $fields[$key] = array(
            'FriendlyName' => pgc_coin_label($coin),
            'Type'         => 'text',
            'Size'         => '60',
            'Description'  => $icon . 'Payout wallet for <code>' . htmlspecialchars($coinId, ENT_QUOTES) . '</code>. Leave blank to not accept this coin.',
        );
    }

    return $fields;
}

function paygatecrypto_activate()
{
    return array('status' => 'success', 'description' => 'Crypto (individual coins) gateway activated.');
}

function paygatecrypto_deactivate()
{
    return array('status' => 'success', 'description' => 'Crypto (individual coins) gateway deactivated.');
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Coins that have a payout wallet configured (i.e. enabled), keyed by coin id.
 * Each entry is the coin map row plus a 'wallet' key.
 */
function paygatecrypto_enabled_coins($params, $map = null)
{
    if ($map === null) {
        $map = pgc_coin_map();
    }
    $out = array();
    foreach ($map as $coinId => $coin) {
        $key    = pgc_wallet_setting_key($coinId);
        $wallet = isset($params[$key]) ? trim($params[$key]) : '';
        if ($wallet !== '') {
            $coin['wallet'] = $wallet;
            $out[$coinId]   = $coin;
        }
    }
    return $out;
}

/**
 * Run the convert -> (optional fees) -> minimum -> wallet -> QR pipeline for a
 * single coin. Returns an array with keys: address, display_total, qr — or an
 * array with an 'error' key on failure.
 */
function paygatecrypto_generate($coin, $amount, $currency, $feesOn, $callbackUrl)
{
    $path = $coin['path'];

    // 1) Convert the fiat invoice total into the selected coin.
    $conv = pgc_get_json(PGC_API_BASE . $path . '/convert.php?value=' . $amount . '&from=' . strtolower($currency));
    if (!$conv || !isset($conv['value_coin'])) {
        return array('error' => 'Payment could not be processed, please try again (unsupported store currency).');
    }
    $payinTotal = (float) $conv['value_coin'];

    // 2) Optionally add estimated blockchain fees.
    if ($feesOn) {
        $fees = pgc_get_json(PGC_API_BASE . $path . '/fees.php');
        if (!$fees || !isset($fees['estimated_cost_currency']['USD'])) {
            return array('error' => 'Failed to get estimated blockchain fees, please try again.');
        }
        $revert = pgc_get_json(PGC_API_BASE . $path . '/convert.php?value=' . $fees['estimated_cost_currency']['USD'] . '&from=usd');
        if (!$revert || !isset($revert['value_coin'])) {
            return array('error' => 'Payment could not be processed, please try again (fee conversion failed).');
        }
        $payinTotal += (float) $revert['value_coin'];
    }

    // 3) Enforce the coin minimum.
    $info = pgc_get_json(PGC_API_BASE . $path . '/info.php');
    if (!$info || !isset($info['minimum'])) {
        return array('error' => 'Payment could not be processed (failed to fetch minimum coin amount).');
    }
    if ($payinTotal < (float) $info['minimum']) {
        return array('error' => 'The order total is below the minimum allowed for the selected coin. Please choose another coin.');
    }

    // 4) Generate the unique pay-in address.
    $wallet = pgc_get_json(PGC_API_BASE . $path . '/wallet.php?address=' . rawurlencode($coin['wallet']) . '&callback=' . urlencode($callbackUrl));
    if (!$wallet || !isset($wallet['address_in'])) {
        return array('error' => 'Payment could not be processed due to an invalid payout wallet, please contact the website admin.');
    }
    $addressIn = $wallet['address_in'];

    // 5) Generate the QR code.
    $qr = pgc_get_json(PGC_API_BASE . $path . '/qrcode.php?address=' . urlencode($addressIn));
    if (!$qr || !isset($qr['qr_code'])) {
        return array('error' => 'Unable to generate the payment QR code, please try again.');
    }

    return array(
        'address'       => $addressIn,
        'display_total' => $payinTotal,
        'qr'            => $qr['qr_code'],
    );
}

/**
 * Render the payment-address / QR block once an address exists for the invoice.
 */
function paygatecrypto_render_payment($coinLabel, $address, $displayTotal, $qrBase64, $resetUrl)
{
    $html  = '<div class="pgc-pay" style="text-align:center;max-width:420px;margin:0 auto;">';
    $html .= '<h3 style="margin-bottom:10px;">Please complete your payment</h3>';
    if ($qrBase64 !== '') {
        $html .= '<div><img style="max-width:80%;height:auto;" src="data:image/png;base64,' . htmlspecialchars($qrBase64, ENT_QUOTES) . '" alt="' . htmlspecialchars($address, ENT_QUOTES) . '"></div>';
    }
    $html .= '<p style="margin-top:12px;">Please send<br><strong style="font-size:1.15em;">' . htmlspecialchars($displayTotal, ENT_QUOTES) . ' ' . htmlspecialchars($coinLabel, ENT_QUOTES) . '</strong><br>to the address:</p>';
    $html .= '<p style="word-break:break-all;"><strong>' . htmlspecialchars($address, ENT_QUOTES) . '</strong></p>';
    $html .= '<p style="font-size:0.85em;"><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES) . '">Pay with a different coin</a></p>';
    $html .= '</div>';
    return $html;
}

/**
 * Add a query parameter to a URL, choosing ? or & correctly.
 */
function paygatecrypto_url_add($url, $param)
{
    return $url . ((strpos($url, '?') !== false) ? '&' : '?') . $param;
}

/**
 * Render the coin selector (icons loaded live from PayGate).
 *
 * IMPORTANT: this uses plain GET links, NOT a <form>. WHMCS already wraps the
 * invoice payment area in its own <form>; a nested form here submits WHMCS's
 * outer form instead and bounces the customer to the client area. Each coin is
 * a link back to the invoice with ?pgc_coin=<id>, which re-runs this gateway
 * and renders the QR code in place — the same way the original per-coin modules
 * displayed the QR on a normal page view.
 */
function paygatecrypto_render_selector($description, $coins, $invoiceLink)
{
    $html  = '';
    if ($description !== '') {
        $html .= '<p>' . htmlspecialchars($description, ENT_QUOTES) . '</p>';
    }
    $html .= '<div class="pgc-coin-select" style="max-width:420px;margin:0 auto;text-align:left;">';
    $html .= '<p style="font-weight:600;">Select the coin you want to pay with:</p>';

    foreach ($coins as $coinId => $coin) {
        $icon = !empty($coin['logo'])
            ? '<img src="' . htmlspecialchars($coin['logo'], ENT_QUOTES) . '" alt="" style="width:24px;height:24px;border-radius:50%;">'
            : '';
        $href = paygatecrypto_url_add($invoiceLink, 'pgc_coin=' . urlencode($coinId));
        $html .= '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '" '
              . 'style="display:flex;align-items:center;gap:10px;margin:6px 0;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:inherit;">';
        $html .= $icon;
        $html .= '<span>' . htmlspecialchars(pgc_coin_label($coin), ENT_QUOTES) . '</span>';
        $html .= '<span style="margin-left:auto;font-weight:600;">Pay &raquo;</span>';
        $html .= '</a>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Render the QR / address block for the coin already stored on the invoice.
 * Re-fetches the QR (cheap) rather than storing a large base64 blob.
 */
function paygatecrypto_render_stored($payload, $invoiceLink)
{
    $qr    = pgc_get_json(PGC_API_BASE . pgc_coin_path($payload['coin']) . '/qrcode.php?address=' . urlencode($payload['pay_to_address']));
    $qrB64 = ($qr && isset($qr['qr_code'])) ? $qr['qr_code'] : '';
    return paygatecrypto_render_payment(
        isset($payload['coin_label']) ? $payload['coin_label'] : $payload['coin'],
        $payload['pay_to_address'],
        $payload['display_total'],
        $qrB64,
        paygatecrypto_url_add($invoiceLink, 'pgc_reset=1')
    );
}

/* -------------------------------------------------------------------------
 * Invoice payment area
 * ---------------------------------------------------------------------- */

function paygatecrypto_link($params)
{
    $amount      = $params['amount'];
    $invoiceId   = $params['invoiceid'];
    $currency    = $params['currency'];
    $systemUrl   = rtrim($params['systemurl'], '/');
    $invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
    $feesOn      = (isset($params['blockchain_fees']) && $params['blockchain_fees'] === 'on');
    $tolerance   = $params['underpaid_tolerance'];
    $description = isset($params['description']) ? $params['description'] : '';

    $map     = pgc_coin_map();
    $enabled = paygatecrypto_enabled_coins($params, $map);

    if (empty($enabled)) {
        return 'No cryptocurrencies are currently available. Please contact the store administrator.';
    }

    // Selection + reset come in as GET params (see paygatecrypto_render_selector).
    $reset   = isset($_REQUEST['pgc_reset']);
    $chosen  = isset($_REQUEST['pgc_coin']) ? (string) $_REQUEST['pgc_coin'] : '';
    $resetUrl = paygatecrypto_url_add($invoiceLink, 'pgc_reset=1');

    $payload    = pgc_read_invoice_payload($invoiceId);
    $haveStored = ($payload && isset($payload['v']) && $payload['v'] === 'individual' && !empty($payload['pay_to_address']));

    // "Pay with a different coin" -> always show the selector.
    if ($reset) {
        return paygatecrypto_render_selector($description, $enabled, $invoiceLink);
    }

    // A specific coin was clicked.
    if ($chosen !== '' && isset($enabled[$chosen])) {
        // Same coin already generated -> reuse the stored address (no new address).
        if ($haveStored && strtolower($payload['coin']) === strtolower($chosen)) {
            return paygatecrypto_render_stored($payload, $invoiceLink);
        }

        // New or switched coin -> generate a fresh pay-in address.
        $coin  = $enabled[$chosen];
        $nonce = pgc_make_nonce();
        $sig   = pgc_sign($invoiceId, $nonce);
        $callbackUrl = $systemUrl . '/modules/gateways/callback/paygatecrypto.php?invoice_id=' . urlencode($invoiceId) . '&sig=' . $sig;

        $result = paygatecrypto_generate($coin, $amount, $currency, $feesOn, $callbackUrl);
        if (isset($result['error'])) {
            return 'Error: ' . htmlspecialchars($result['error'], ENT_QUOTES)
                . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES) . '">Try a different coin</a></p>';
        }

        pgc_write_invoice_payload($invoiceId, array(
            'v'              => 'individual',
            'coin'           => $coin['id'],
            'coin_label'     => pgc_coin_label($coin),
            'path'           => $coin['path'],
            'wallet'         => $coin['wallet'],
            'pay_to_address' => $result['address'],
            'display_total'  => $result['display_total'],
            'amount'         => $amount,        // expected fiat
            'currency'       => $currency,
            'tolerance'      => $tolerance,
            'fees'           => $feesOn ? '1' : '0',
            'nonce'          => $nonce,
        ));

        return paygatecrypto_render_payment(
            pgc_coin_label($coin),
            $result['address'],
            $result['display_total'],
            $result['qr'],
            $resetUrl
        );
    }

    // No explicit choice, but an address already exists -> show it.
    if ($haveStored) {
        return paygatecrypto_render_stored($payload, $invoiceLink);
    }

    // Otherwise show the coin selector.
    return paygatecrypto_render_selector($description, $enabled, $invoiceLink);
}
