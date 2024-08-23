<?php
// Retrieve the invoice ID and received amount from the query string
$invoiceId = $_GET['invoice_id'];
$value_coin = $_GET['value_coin'];
$txid_in = $_GET['txid_in'];
$coin = $_GET['coin'];


if (empty($invoiceId)) {
    die("Invalid invoice ID");
}

// Include WHMCS required files
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Get the gateway module name from the filename
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Retrieve the invoice information using localAPI
$invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

if ($invoice['result'] == 'success' && $invoice['status'] != 'Paid') {
    // Get the client's currency
    $clientId = $invoice['userid'];
    $currency = getCurrency($clientId); 
    $invoiceCurrencyCode = $currency['code']; // Currency code, e.g., USD, EUR, etc.
    $invoiceTotal = $invoice['total']; // Get the total amount of the invoice
    $invoiceNotes = html_entity_decode($invoice['notes']);


$stored_invoice_data = json_decode($invoiceNotes, true);
  if ($stored_invoice_data && isset($stored_invoice_data['coin_to_send'])) {
	  $reference_coin = $stored_invoice_data['coin_to_send'];
	  $reference_coin_amounnt = $stored_invoice_data['crypto_amount_to_send'];
  } else {
die ("Error: Payment could not be processed, invoice has no reference notes data.");	  
  }

    if ($value_coin < $reference_coin_amounnt) {
        die("Error: Payment received is less than the invoice total. Customer sent $value_coin $coin instead of $reference_coin_amounnt $reference_coin");
    } elseif ($reference_coin != $coin) {
		die("Error: Coin mismatch. Customer sent $value_coin $coin instead of $reference_coin_amounnt $reference_coin");
	}

    // Mark the invoice as paid
    $paymentSuccess = [
        'invoiceid' => $invoiceId,
        'transid' => 'polygon_usdt_TXID_' . $txid_in, // Replace with the actual transaction ID if available
        'date' => date('Y-m-d H:i:s'),
    ];

    $result = localAPI('AddInvoicePayment', $paymentSuccess);

    if ($result['result'] == 'success') {
        // Redirect to the invoice page
        $invoiceLink = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceId;
        header("Location: $invoiceLink");
        exit;
    } else {
        // Redirect to the invoice page with an error
        $invoiceLink = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceId;
        header("Location: $invoiceLink&error=payment_failed");
        exit;
    }
} else {
    // Redirect to the invoice page
    $invoiceLink = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceId;
    header("Location: $invoiceLink&error=invalid_invoice");
    exit;
}
?>
