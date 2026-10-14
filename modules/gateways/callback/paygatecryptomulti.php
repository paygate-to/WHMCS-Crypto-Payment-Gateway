<?php
/**
 * PayGate.to — payment confirmation callback for the multicoin (multi mode)
 * gateway. PayGate appends value_coin, coin and txid_in to the callback URL.
 * The received coin is valued in the invoice currency and the invoice is marked
 * paid when it meets the expected amount (with tolerance + optional fees).
 */

$invoiceId = isset($_GET['invoice_id']) ? (int) $_GET['invoice_id'] : 0;
$valueCoin = isset($_GET['value_coin']) ? $_GET['value_coin'] : '';
$paidCoin  = isset($_GET['coin']) ? $_GET['coin'] : '';
$txidIn    = isset($_GET['txid_in']) ? $_GET['txid_in'] : '';
$sig       = isset($_GET['sig']) ? $_GET['sig'] : '';

if ($invoiceId <= 0) {
    die('Invalid invoice ID');
}

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../paygatecrypto/lib.php';

$gatewayModuleName = basename(__FILE__, '.php'); // paygatecryptomulti
$gatewayParams     = getGatewayVariables($gatewayModuleName);
$apiBase           = pgc_api_base(isset($gatewayParams['api_domain']) ? $gatewayParams['api_domain'] : '');

if (!$gatewayParams['type']) {
    die('Module not activated');
}

$payload = pgc_read_invoice_payload($invoiceId);
if (!$payload || !isset($payload['v']) || $payload['v'] !== 'multi') {
    die('Error: invoice has no reference payment data.');
}

if (!pgc_verify_sig($invoiceId, isset($payload['nonce']) ? $payload['nonce'] : '', $sig)) {
    logTransaction($gatewayParams['name'], $_GET, 'Rejected: invalid callback signature');
    pgc_fail(403, 'Invalid callback signature');
}

// PayGate's RSA signature over the exact URL it requested: proves the callback
// came from PayGate and that value_coin / coin were not changed. Required.
$paygateSignature = pgc_verify_paygate_signature(pgc_callback_base($gatewayModuleName));
if (!$paygateSignature['verified']) {
    logTransaction($gatewayParams['name'], $_GET, 'Rejected: PayGate signature ' . $paygateSignature['reason']);
    pgc_fail(403, 'Invalid PayGate signature (' . $paygateSignature['reason'] . ')');
}

// Unconfirmed (pending=1) notifications never change the invoice; the confirmed callback follows.
if (isset($_GET['pending']) && $_GET['pending'] === '1') {
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/plain');
    echo '*ok*';
    exit;
}

$invoice = localAPI('GetInvoice', array('invoiceid' => $invoiceId));
if (!isset($invoice['result']) || $invoice['result'] !== 'success') {
    die('Invalid invoice');
}
if ($invoice['status'] === 'Paid') {
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/plain');
    echo '*ok*';
    exit;
}

$currency = isset($payload['currency']) ? $payload['currency'] : '';

// Value the received coin in the invoice currency.
$info = pgc_get_json($apiBase . pgc_coin_path($paidCoin) . '/info.php');
if (!$info || !isset($info['prices'][$currency])) {
    die('Error: failed to fetch coin pricing for verification.');
}
$coinPrice    = (float) $info['prices'][$currency];
$receivedFiat = (float) $valueCoin * $coinPrice;

$expectedFiat = (float) $payload['amount'];
$tolerance    = (float) $payload['tolerance'];
$minRequired  = $expectedFiat * $tolerance;

if (isset($payload['fees']) && $payload['fees'] === '1') {
    $fees = pgc_get_json($apiBase . pgc_coin_path($paidCoin) . '/fees.php');
    if (!$fees || !isset($fees['estimated_cost_currency'][$currency])) {
        die('Error: failed to fetch coin fee data for verification.');
    }
    $minRequired += (float) $fees['estimated_cost_currency'][$currency];
}

if ($receivedFiat < $minRequired) {
    $note = sprintf(
        '[Underpaid] Received %s %s (~%.2f %s), required minimum: %.2f %s. TXID: %s',
        $valueCoin, strtoupper($paidCoin), $receivedFiat, $currency, $minRequired, $currency, $txidIn
    );
    logTransaction($gatewayParams['name'], $_GET, 'Underpaid');
    die('Error: ' . $note);
}

$transId = 'pgcmulti_' . $paidCoin . '_' . $txidIn;
checkCbTransID($transId);

addInvoicePayment($invoiceId, $transId, '', '', $gatewayModuleName);
logTransaction($gatewayParams['name'], $_GET, 'Successful');

header('HTTP/1.1 200 OK');
header('Content-Type: text/plain');
echo '*ok*';
exit;
