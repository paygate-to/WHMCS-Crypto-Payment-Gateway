<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function ltc_MetaData()
{
    return array(
        'DisplayName' => 'ltc',
        'DisableLocalCreditCardInput' => true,
    );
}

function ltc_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Litecoin',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Litecoin.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Litecoin Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your ltc Wallet address.',
        ),
    );
}

function ltc_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/ltc.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_ltc_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_ltc_response = file_get_contents('https://api.paygate.to/crypto/ltc/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_ltc_currency));


$paygatedotto_ltc_conversion_resp = json_decode($paygatedotto_ltc_response, true);

if ($paygatedotto_ltc_conversion_resp && isset($paygatedotto_ltc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_ltc_final_total = $paygatedotto_ltc_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_ltc_blockchain_response = file_get_contents('https://api.paygate.to/crypto/ltc/fees.php');


$paygatedotto_ltc_blockchain_conversion_resp = json_decode($paygatedotto_ltc_blockchain_response, true);

if ($paygatedotto_ltc_blockchain_conversion_resp && isset($paygatedotto_ltc_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_ltc_response = file_get_contents('https://api.paygate.to/crypto/ltc/convert.php?value=' . $paygatedotto_ltc_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_ltc_conversion_resp = json_decode($paygatedotto_feerevert_ltc_response, true);

if ($paygatedotto_feerevert_ltc_conversion_resp && isset($paygatedotto_feerevert_ltc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_ltc_final_total = $paygatedotto_feerevert_ltc_conversion_resp['value_coin']; 
// output
    $paygatedotto_ltc_blockchain_final_total = $paygatedotto_feerevert_ltc_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_ltc_amount_to_send = $paygatedotto_ltc_final_total + $paygatedotto_ltc_blockchain_final_total;		
	
		} else {
	$paygatedotto_ltc_amount_to_send = $paygatedotto_ltc_final_total;		
		}
		
		
		
$paygatedotto_ltc_gen_wallet = file_get_contents('https://api.paygate.to/crypto/ltc/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_ltc_wallet_decbody = json_decode($paygatedotto_ltc_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_ltc_wallet_decbody && isset($paygatedotto_ltc_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_ltc_gen_addressIn = $paygatedotto_ltc_wallet_decbody['address_in'];
		$paygatedotto_ltc_gen_callback = $paygatedotto_ltc_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_ltc_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_ltc_amount_to_send,
'coin_to_send' => 'ltc'
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
	
	
        $paygatedotto_ltc_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/ltc/qrcode.php?address=' . $paygatedotto_ltc_gen_addressIn);


	$paygatedotto_ltc_qrcode_decbody = json_decode($paygatedotto_ltc_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_ltc_qrcode_decbody && isset($paygatedotto_ltc_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_ltc_gen_qrcode = $paygatedotto_ltc_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_ltc_gen_qrcode . '" alt="' . $paygatedotto_ltc_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_ltc_amount_to_send . '</b> ltc to the address: <br><b>' . $paygatedotto_ltc_gen_addressIn . '</b></div>';
}

function ltc_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'ltc gateway activated successfully.');
}

function ltc_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'ltc gateway deactivated successfully.');
}

function ltc_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function ltc_output($vars)
{
    // Output additional information if needed
}

function ltc_error($vars)
{
    // Handle errors if needed
}
