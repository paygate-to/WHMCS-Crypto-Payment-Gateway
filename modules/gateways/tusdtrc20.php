<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function tusdtrc20_MetaData()
{
    return array(
        'DisplayName' => 'tusdtrc20',
        'DisableLocalCreditCardInput' => true,
    );
}

function tusdtrc20_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'TrueUSD trc20',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto TrueUSD trc20.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'TrueUSD trc20 Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your trc20-tusd Wallet address.',
        ),
    );
}

function tusdtrc20_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/tusdtrc20.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_tusdtrc20_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_tusdtrc20_response = file_get_contents('https://api.paygate.to/crypto/trc20/tusd/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_tusdtrc20_currency));


$paygatedotto_tusdtrc20_conversion_resp = json_decode($paygatedotto_tusdtrc20_response, true);

if ($paygatedotto_tusdtrc20_conversion_resp && isset($paygatedotto_tusdtrc20_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_tusdtrc20_final_total = $paygatedotto_tusdtrc20_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_tusdtrc20_blockchain_response = file_get_contents('https://api.paygate.to/crypto/trc20/tusd/fees.php');


$paygatedotto_tusdtrc20_blockchain_conversion_resp = json_decode($paygatedotto_tusdtrc20_blockchain_response, true);

if ($paygatedotto_tusdtrc20_blockchain_conversion_resp && isset($paygatedotto_tusdtrc20_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_tusdtrc20_response = file_get_contents('https://api.paygate.to/crypto/trc20/tusd/convert.php?value=' . $paygatedotto_tusdtrc20_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_tusdtrc20_conversion_resp = json_decode($paygatedotto_feerevert_tusdtrc20_response, true);

if ($paygatedotto_feerevert_tusdtrc20_conversion_resp && isset($paygatedotto_feerevert_tusdtrc20_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_tusdtrc20_final_total = $paygatedotto_feerevert_tusdtrc20_conversion_resp['value_coin']; 
// output
    $paygatedotto_tusdtrc20_blockchain_final_total = $paygatedotto_feerevert_tusdtrc20_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_tusdtrc20_amount_to_send = $paygatedotto_tusdtrc20_final_total + $paygatedotto_tusdtrc20_blockchain_final_total;		
	
		} else {
	$paygatedotto_tusdtrc20_amount_to_send = $paygatedotto_tusdtrc20_final_total;		
		}
		
		
		
$paygatedottocryptogateway_tusdtrc20_response_minimum = file_get_contents('https://api.paygate.to/crypto/trc20/tusd/info.php');
$paygatedottocryptogateway_tusdtrc20_conversion_resp_minimum = json_decode($paygatedottocryptogateway_tusdtrc20_response_minimum, true);
if ($paygatedottocryptogateway_tusdtrc20_conversion_resp_minimum && isset($paygatedottocryptogateway_tusdtrc20_conversion_resp_minimum['minimum'])) {
    $paygatedottocryptogateway_tusdtrc20_final_total_minimum = $paygatedottocryptogateway_tusdtrc20_conversion_resp_minimum['minimum'];
    if ($paygatedotto_tusdtrc20_amount_to_send < $paygatedottocryptogateway_tusdtrc20_final_total_minimum) {
        return "Error: Payment could not be processed, order total crypto amount to send is less than the minimum allowed for the selected coin";
    }
} else {
    return "Error: Payment could not be processed, can't fetch crypto minimum allowed amount";
}
$paygatedotto_tusdtrc20_gen_wallet = file_get_contents('https://api.paygate.to/crypto/trc20/tusd/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_tusdtrc20_wallet_decbody = json_decode($paygatedotto_tusdtrc20_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_tusdtrc20_wallet_decbody && isset($paygatedotto_tusdtrc20_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_tusdtrc20_gen_addressIn = $paygatedotto_tusdtrc20_wallet_decbody['address_in'];
		$paygatedotto_tusdtrc20_gen_callback = $paygatedotto_tusdtrc20_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_tusdtrc20_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_tusdtrc20_amount_to_send,
'coin_to_send' => 'trc20_tusd'
));

		
		 // Update the invoice description to include address_in
            $invoiceDescription = $paygatedotto_jsonObject;

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paygatedotto_tusdtrc20_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/trc20/tusd/qrcode.php?address=' . $paygatedotto_tusdtrc20_gen_addressIn);


	$paygatedotto_tusdtrc20_qrcode_decbody = json_decode($paygatedotto_tusdtrc20_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_tusdtrc20_qrcode_decbody && isset($paygatedotto_tusdtrc20_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_tusdtrc20_gen_qrcode = $paygatedotto_tusdtrc20_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_tusdtrc20_gen_qrcode . '" alt="' . $paygatedotto_tusdtrc20_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_tusdtrc20_amount_to_send . '</b> trc20/tusd to the address: <br><b>' . $paygatedotto_tusdtrc20_gen_addressIn . '</b></div>';
}

function tusdtrc20_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'tusdtrc20 gateway activated successfully.');
}

function tusdtrc20_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'tusdtrc20 gateway deactivated successfully.');
}

function tusdtrc20_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function tusdtrc20_output($vars)
{
    // Output additional information if needed
}

function tusdtrc20_error($vars)
{
    // Handle errors if needed
}
