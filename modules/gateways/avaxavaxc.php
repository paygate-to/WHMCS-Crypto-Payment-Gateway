<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function avaxavaxc_MetaData()
{
    return array(
        'DisplayName' => 'avaxavaxc',
        'DisableLocalCreditCardInput' => true,
    );
}

function avaxavaxc_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'AVAX avax-c',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto AVAX avax-c.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'AVAX avax-c Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your avax-c-avax Wallet address.',
        ),
    );
}

function avaxavaxc_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/avaxavaxc.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_avaxavaxc_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_avaxavaxc_response = file_get_contents('https://api.paygate.to/crypto/avax-c/avax/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_avaxavaxc_currency));


$paygatedotto_avaxavaxc_conversion_resp = json_decode($paygatedotto_avaxavaxc_response, true);

if ($paygatedotto_avaxavaxc_conversion_resp && isset($paygatedotto_avaxavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_avaxavaxc_final_total = $paygatedotto_avaxavaxc_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_avaxavaxc_blockchain_response = file_get_contents('https://api.paygate.to/crypto/avax-c/avax/fees.php');


$paygatedotto_avaxavaxc_blockchain_conversion_resp = json_decode($paygatedotto_avaxavaxc_blockchain_response, true);

if ($paygatedotto_avaxavaxc_blockchain_conversion_resp && isset($paygatedotto_avaxavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_avaxavaxc_response = file_get_contents('https://api.paygate.to/crypto/avax-c/avax/convert.php?value=' . $paygatedotto_avaxavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_avaxavaxc_conversion_resp = json_decode($paygatedotto_feerevert_avaxavaxc_response, true);

if ($paygatedotto_feerevert_avaxavaxc_conversion_resp && isset($paygatedotto_feerevert_avaxavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_avaxavaxc_final_total = $paygatedotto_feerevert_avaxavaxc_conversion_resp['value_coin']; 
// output
    $paygatedotto_avaxavaxc_blockchain_final_total = $paygatedotto_feerevert_avaxavaxc_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_avaxavaxc_amount_to_send = $paygatedotto_avaxavaxc_final_total + $paygatedotto_avaxavaxc_blockchain_final_total;		
	
		} else {
	$paygatedotto_avaxavaxc_amount_to_send = $paygatedotto_avaxavaxc_final_total;		
		}
		
		
		
$paygatedotto_avaxavaxc_gen_wallet = file_get_contents('https://api.paygate.to/crypto/avax-c/avax/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_avaxavaxc_wallet_decbody = json_decode($paygatedotto_avaxavaxc_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_avaxavaxc_wallet_decbody && isset($paygatedotto_avaxavaxc_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_avaxavaxc_gen_addressIn = $paygatedotto_avaxavaxc_wallet_decbody['address_in'];
		$paygatedotto_avaxavaxc_gen_callback = $paygatedotto_avaxavaxc_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_avaxavaxc_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_avaxavaxc_amount_to_send,
'coin_to_send' => 'avax-c_avax'
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
	
	
        $paygatedotto_avaxavaxc_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/avax-c/avax/qrcode.php?address=' . $paygatedotto_avaxavaxc_gen_addressIn);


	$paygatedotto_avaxavaxc_qrcode_decbody = json_decode($paygatedotto_avaxavaxc_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_avaxavaxc_qrcode_decbody && isset($paygatedotto_avaxavaxc_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_avaxavaxc_gen_qrcode = $paygatedotto_avaxavaxc_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_avaxavaxc_gen_qrcode . '" alt="' . $paygatedotto_avaxavaxc_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_avaxavaxc_amount_to_send . '</b> avax-c/avax to the address: <br><b>' . $paygatedotto_avaxavaxc_gen_addressIn . '</b></div>';
}

function avaxavaxc_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'avaxavaxc gateway activated successfully.');
}

function avaxavaxc_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'avaxavaxc gateway deactivated successfully.');
}

function avaxavaxc_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function avaxavaxc_output($vars)
{
    // Output additional information if needed
}

function avaxavaxc_error($vars)
{
    // Handle errors if needed
}
