<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function usdcavaxc_MetaData()
{
    return array(
        'DisplayName' => 'usdcavaxc',
        'DisableLocalCreditCardInput' => true,
    );
}

function usdcavaxc_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'USD Coin (Native) avax-c',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto USD Coin (Native) avax-c.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'USD Coin (Native) avax-c Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your avax-c-usdc Wallet address.',
        ),
    );
}

function usdcavaxc_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/usdcavaxc.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_usdcavaxc_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_usdcavaxc_response = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_usdcavaxc_currency));


$paygatedotto_usdcavaxc_conversion_resp = json_decode($paygatedotto_usdcavaxc_response, true);

if ($paygatedotto_usdcavaxc_conversion_resp && isset($paygatedotto_usdcavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_usdcavaxc_final_total = $paygatedotto_usdcavaxc_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_usdcavaxc_blockchain_response = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc/fees.php');


$paygatedotto_usdcavaxc_blockchain_conversion_resp = json_decode($paygatedotto_usdcavaxc_blockchain_response, true);

if ($paygatedotto_usdcavaxc_blockchain_conversion_resp && isset($paygatedotto_usdcavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_usdcavaxc_response = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc/convert.php?value=' . $paygatedotto_usdcavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_usdcavaxc_conversion_resp = json_decode($paygatedotto_feerevert_usdcavaxc_response, true);

if ($paygatedotto_feerevert_usdcavaxc_conversion_resp && isset($paygatedotto_feerevert_usdcavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_usdcavaxc_final_total = $paygatedotto_feerevert_usdcavaxc_conversion_resp['value_coin']; 
// output
    $paygatedotto_usdcavaxc_blockchain_final_total = $paygatedotto_feerevert_usdcavaxc_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_usdcavaxc_amount_to_send = $paygatedotto_usdcavaxc_final_total + $paygatedotto_usdcavaxc_blockchain_final_total;		
	
		} else {
	$paygatedotto_usdcavaxc_amount_to_send = $paygatedotto_usdcavaxc_final_total;		
		}
		
		
		
$paygatedottocryptogateway_usdcavaxc_response_minimum = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc/info.php');
$paygatedottocryptogateway_usdcavaxc_conversion_resp_minimum = json_decode($paygatedottocryptogateway_usdcavaxc_response_minimum, true);
if ($paygatedottocryptogateway_usdcavaxc_conversion_resp_minimum && isset($paygatedottocryptogateway_usdcavaxc_conversion_resp_minimum['minimum'])) {
    $paygatedottocryptogateway_usdcavaxc_final_total_minimum = $paygatedottocryptogateway_usdcavaxc_conversion_resp_minimum['minimum'];
    if ($paygatedotto_usdcavaxc_amount_to_send < $paygatedottocryptogateway_usdcavaxc_final_total_minimum) {
        return "Error: Payment could not be processed, order total crypto amount to send is less than the minimum allowed for the selected coin";
    }
} else {
    return "Error: Payment could not be processed, can't fetch crypto minimum allowed amount";
}
$paygatedotto_usdcavaxc_gen_wallet = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_usdcavaxc_wallet_decbody = json_decode($paygatedotto_usdcavaxc_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_usdcavaxc_wallet_decbody && isset($paygatedotto_usdcavaxc_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_usdcavaxc_gen_addressIn = $paygatedotto_usdcavaxc_wallet_decbody['address_in'];
		$paygatedotto_usdcavaxc_gen_callback = $paygatedotto_usdcavaxc_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_usdcavaxc_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_usdcavaxc_amount_to_send,
'coin_to_send' => 'avax-c_usdc'
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
	
	
        $paygatedotto_usdcavaxc_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc/qrcode.php?address=' . $paygatedotto_usdcavaxc_gen_addressIn);


	$paygatedotto_usdcavaxc_qrcode_decbody = json_decode($paygatedotto_usdcavaxc_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_usdcavaxc_qrcode_decbody && isset($paygatedotto_usdcavaxc_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_usdcavaxc_gen_qrcode = $paygatedotto_usdcavaxc_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_usdcavaxc_gen_qrcode . '" alt="' . $paygatedotto_usdcavaxc_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_usdcavaxc_amount_to_send . '</b> avax-c/usdc to the address: <br><b>' . $paygatedotto_usdcavaxc_gen_addressIn . '</b></div>';
}

function usdcavaxc_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'usdcavaxc gateway activated successfully.');
}

function usdcavaxc_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'usdcavaxc gateway deactivated successfully.');
}

function usdcavaxc_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function usdcavaxc_output($vars)
{
    // Output additional information if needed
}

function usdcavaxc_error($vars)
{
    // Handle errors if needed
}
