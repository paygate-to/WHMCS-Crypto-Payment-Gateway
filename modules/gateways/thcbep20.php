<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function thcbep20_MetaData()
{
    return array(
        'DisplayName' => 'thcbep20',
        'DisableLocalCreditCardInput' => true,
    );
}

function thcbep20_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Transhuman Coin bep20',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Transhuman Coin bep20.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Transhuman Coin bep20 Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your bep20-thc Wallet address.',
        ),
    );
}

function thcbep20_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/thcbep20.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_thcbep20_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_thcbep20_response = file_get_contents('https://api.paygate.to/crypto/bep20/thc/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_thcbep20_currency));


$paygatedotto_thcbep20_conversion_resp = json_decode($paygatedotto_thcbep20_response, true);

if ($paygatedotto_thcbep20_conversion_resp && isset($paygatedotto_thcbep20_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_thcbep20_final_total = $paygatedotto_thcbep20_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_thcbep20_blockchain_response = file_get_contents('https://api.paygate.to/crypto/bep20/thc/fees.php');


$paygatedotto_thcbep20_blockchain_conversion_resp = json_decode($paygatedotto_thcbep20_blockchain_response, true);

if ($paygatedotto_thcbep20_blockchain_conversion_resp && isset($paygatedotto_thcbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_thcbep20_response = file_get_contents('https://api.paygate.to/crypto/bep20/thc/convert.php?value=' . $paygatedotto_thcbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_thcbep20_conversion_resp = json_decode($paygatedotto_feerevert_thcbep20_response, true);

if ($paygatedotto_feerevert_thcbep20_conversion_resp && isset($paygatedotto_feerevert_thcbep20_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_thcbep20_final_total = $paygatedotto_feerevert_thcbep20_conversion_resp['value_coin']; 
// output
    $paygatedotto_thcbep20_blockchain_final_total = $paygatedotto_feerevert_thcbep20_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_thcbep20_amount_to_send = $paygatedotto_thcbep20_final_total + $paygatedotto_thcbep20_blockchain_final_total;		
	
		} else {
	$paygatedotto_thcbep20_amount_to_send = $paygatedotto_thcbep20_final_total;		
		}
		
		
		
$paygatedottocryptogateway_thcbep20_response_minimum = file_get_contents('https://api.paygate.to/crypto/bep20/thc/info.php');
$paygatedottocryptogateway_thcbep20_conversion_resp_minimum = json_decode($paygatedottocryptogateway_thcbep20_response_minimum, true);
if ($paygatedottocryptogateway_thcbep20_conversion_resp_minimum && isset($paygatedottocryptogateway_thcbep20_conversion_resp_minimum['minimum'])) {
    $paygatedottocryptogateway_thcbep20_final_total_minimum = $paygatedottocryptogateway_thcbep20_conversion_resp_minimum['minimum'];
    if ($paygatedotto_thcbep20_amount_to_send < $paygatedottocryptogateway_thcbep20_final_total_minimum) {
        return "Error: Payment could not be processed, order total crypto amount to send is less than the minimum allowed for the selected coin";
    }
} else {
    return "Error: Payment could not be processed, can't fetch crypto minimum allowed amount";
}
$paygatedotto_thcbep20_gen_wallet = file_get_contents('https://api.paygate.to/crypto/bep20/thc/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_thcbep20_wallet_decbody = json_decode($paygatedotto_thcbep20_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_thcbep20_wallet_decbody && isset($paygatedotto_thcbep20_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_thcbep20_gen_addressIn = $paygatedotto_thcbep20_wallet_decbody['address_in'];
		$paygatedotto_thcbep20_gen_callback = $paygatedotto_thcbep20_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_thcbep20_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_thcbep20_amount_to_send,
'coin_to_send' => 'bep20_thc'
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
	
	
        $paygatedotto_thcbep20_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/bep20/thc/qrcode.php?address=' . $paygatedotto_thcbep20_gen_addressIn);


	$paygatedotto_thcbep20_qrcode_decbody = json_decode($paygatedotto_thcbep20_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_thcbep20_qrcode_decbody && isset($paygatedotto_thcbep20_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_thcbep20_gen_qrcode = $paygatedotto_thcbep20_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_thcbep20_gen_qrcode . '" alt="' . $paygatedotto_thcbep20_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_thcbep20_amount_to_send . '</b> bep20/thc to the address: <br><b>' . $paygatedotto_thcbep20_gen_addressIn . '</b></div>';
}

function thcbep20_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'thcbep20 gateway activated successfully.');
}

function thcbep20_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'thcbep20 gateway deactivated successfully.');
}

function thcbep20_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function thcbep20_output($vars)
{
    // Output additional information if needed
}

function thcbep20_error($vars)
{
    // Handle errors if needed
}
