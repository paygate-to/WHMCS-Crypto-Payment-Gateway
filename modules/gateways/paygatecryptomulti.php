<?php
/**
 * PayGate.to — Multicoin ("multi mode") Crypto Payment Gateway for WHMCS.
 *
 * A single payment method that accepts ANY supported coin via PayGate's hosted
 * checkout page. The admin sets one payout wallet per chain family; the customer
 * is redirected to the hosted page (optionally on your own custom domain, with
 * your branding) where they pick a coin and pay. The callback
 * (modules/gateways/callback/paygatecryptomulti.php) confirms payment by valuing
 * the received coin in the invoice currency.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/paygatecrypto/lib.php';

function paygatecryptomulti_MetaData()
{
    return array(
        'DisplayName'                 => 'Crypto Payment Gateway (Multicoin / Multi Mode)',
        'APIVersion'                  => '1.1',
        'DisableLocalCreditCardInput' => true,
    );
}

function paygatecryptomulti_config()
{
    return array(
        'FriendlyName' => array(
            'Type'  => 'System',
            'Value' => 'Crypto Payment Gateway — Multicoin (No KYC, instant payouts)',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type'         => 'textarea',
            'Rows'         => '3',
            'Cols'         => '40',
            'Default'      => 'Pay with any supported cryptocurrency.',
            'Description'  => 'Shown to the customer on the invoice.',
        ),
        'custom_domain' => array(
            'FriendlyName' => 'Custom Domain',
            'Type'         => 'text',
            'Default'      => 'checkout.paygate.sbs',
            'Description'  => 'Hosted checkout domain. Follow the PayGate custom domain guide to use your own domain.',
        ),
        'wallet_evm' => array(
            'FriendlyName' => 'EVM Wallet Address',
            'Type'         => 'text', 'Size' => '60',
            'Description'  => 'EVM-compatible wallet (ERC20 / ETH / BEP20 / Polygon / Optimism / Arbitrum / Base / Avax-C).',
        ),
        'wallet_btc' => array(
            'FriendlyName' => 'Bitcoin Wallet Address (BTC)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'wallet_bitcoincash' => array(
            'FriendlyName' => 'Bitcoin Cash Wallet Address (BCH)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'wallet_ltc' => array(
            'FriendlyName' => 'Litecoin Wallet Address (LTC)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'wallet_doge' => array(
            'FriendlyName' => 'Dogecoin Wallet Address (DOGE)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'wallet_solana' => array(
            'FriendlyName' => 'Solana Wallet Address (SOL)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'wallet_trc20' => array(
            'FriendlyName' => 'TRC20 Wallet Address (Tron/USDT)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'wallet_xmr' => array(
            'FriendlyName' => 'Monero Wallet Address (XMR)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'wallet_zec' => array(
            'FriendlyName' => 'Zcash Wallet Address (ZEC)',
            'Type'         => 'text', 'Size' => '60',
        ),
        'underpaid_tolerance' => array(
            'FriendlyName' => 'Underpaid Tolerance',
            'Type'         => 'dropdown',
            'Options'      => array(
                '1' => '0%', '0.99' => '1%', '0.98' => '2%', '0.97' => '3%', '0.96' => '4%',
                '0.95' => '5%', '0.94' => '6%', '0.93' => '7%', '0.92' => '8%', '0.91' => '9%',
                '0.90' => '10%',
            ),
            'Default'      => '0.99',
            'Description'  => 'Tolerate underpayment when a customer sends slightly less than required.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type'         => 'yesno',
            'Default'      => 'off',
            'Description'  => 'Add estimated blockchain fees to the order total.',
        ),
        'logo_url' => array(
            'FriendlyName' => 'Custom Logo URL',
            'Type'         => 'text', 'Size' => '60',
            'Description'  => 'Your brand/website logo for the hosted checkout page.',
        ),
        'background_color' => array(
            'FriendlyName' => 'Background Color',
            'Type'         => 'text',
            'Description'  => 'HEX color for the hosted page background.',
        ),
        'theme_color' => array(
            'FriendlyName' => 'Theme Color',
            'Type'         => 'text',
            'Description'  => 'HEX color for the hosted page theme.',
        ),
        'button_color' => array(
            'FriendlyName' => 'Button Color',
            'Type'         => 'text',
            'Description'  => 'HEX color for the hosted page pay button.',
        ),
    );
}

function paygatecryptomulti_activate()
{
    return array('status' => 'success', 'description' => 'Crypto (multicoin) gateway activated.');
}

function paygatecryptomulti_deactivate()
{
    return array('status' => 'success', 'description' => 'Crypto (multicoin) gateway deactivated.');
}

function paygatecryptomulti_link($params)
{
    $amount    = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $currency  = $params['currency'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $feesValue = (isset($params['blockchain_fees']) && $params['blockchain_fees'] === 'on') ? '1' : '0';
    $tolerance = $params['underpaid_tolerance'];
    $domain    = rtrim(str_replace(array('https://', 'http://'), '', $params['custom_domain']), '/');
    if ($domain === '') {
        $domain = 'checkout.paygate.sbs';
    }

    // Reuse an existing hosted session for this invoice if one was created.
    $payload = pgc_read_invoice_payload($invoiceId);
    if (!$payload || !isset($payload['v']) || $payload['v'] !== 'multi' || empty($payload['payment_token'])) {
        $nonce = pgc_make_nonce();
        $sig   = pgc_sign($invoiceId, $nonce);
        $callbackUrl = $systemUrl . '/modules/gateways/callback/paygatecryptomulti.php?invoice_id=' . urlencode($invoiceId) . '&sig=' . $sig;

        $payloadOut = array(
            'fiat_amount'   => $amount,
            'fiat_currency' => $currency,
            'callback'      => $callbackUrl,
        );
        foreach (array(
            'evm'         => 'wallet_evm',
            'btc'         => 'wallet_btc',
            'bitcoincash' => 'wallet_bitcoincash',
            'ltc'         => 'wallet_ltc',
            'doge'        => 'wallet_doge',
            'solana'      => 'wallet_solana',
            'trc20'       => 'wallet_trc20',
            'xmr'         => 'wallet_xmr',
            'zec'         => 'wallet_zec',
        ) as $family => $field) {
            if (!empty($params[$field])) {
                $payloadOut[$family] = trim($params[$field]);
            }
        }

        $resp = pgc_http_post_json(PGC_MULTI_WALLET_URL, json_encode($payloadOut));
        $dec  = $resp ? json_decode($resp, true) : null;

        if (!is_array($dec) || !isset($dec['payment_token'])) {
            return 'Error: payment could not be initialised, please contact the website admin (check that at least one wallet address is configured).';
        }

        pgc_write_invoice_payload($invoiceId, array(
            'v'             => 'multi',
            'payment_token' => $dec['payment_token'],
            'amount'        => $amount,
            'currency'      => $currency,
            'tolerance'     => $tolerance,
            'fees'          => $feesValue,
            'nonce'         => $nonce,
        ));

        $paymentToken = $dec['payment_token'];
    } else {
        $paymentToken = $payload['payment_token'];
        $feesValue    = isset($payload['fees']) ? $payload['fees'] : $feesValue;
    }

    // Build the hosted checkout URL (with optional branding).
    $url = 'https://' . $domain . '/crypto/hosted.php?payment_token=' . $paymentToken . '&add_fees=' . $feesValue;
    if (!empty($params['logo_url'])) {
        $url .= '&logo=' . urlencode($params['logo_url']);
    }
    if (!empty($params['background_color'])) {
        $url .= '&background=' . urlencode($params['background_color']);
    }
    if (!empty($params['theme_color'])) {
        $url .= '&theme=' . urlencode($params['theme_color']);
    }
    if (!empty($params['button_color'])) {
        $url .= '&button=' . urlencode($params['button_color']);
    }

    $desc = isset($params['description']) ? $params['description'] : '';
    $html = '';
    if ($desc !== '') {
        $html .= '<p>' . htmlspecialchars($desc, ENT_QUOTES) . '</p>';
    }
    $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" class="btn btn-primary" style="display:inline-block;padding:10px 22px;">Pay with cryptocurrency</a>';
    return $html;
}
