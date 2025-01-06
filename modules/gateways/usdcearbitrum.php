<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function usdcearbitrum_MetaData()
{
    return array(
        'DisplayName' => 'usdcearbitrum',
        'DisableLocalCreditCardInput' => true,
    );
}

function usdcearbitrum_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'USD Coin (Bridged) arbitrum',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto USD Coin (Bridged) arbitrum.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'USD Coin (Bridged) arbitrum Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your arbitrum-usdc.e Wallet address.',
        ),
    );
}

function usdcearbitrum_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/usdcearbitrum.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_usdcearbitrum_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_usdcearbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdc.e/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_usdcearbitrum_currency));


$paygatedotto_usdcearbitrum_conversion_resp = json_decode($paygatedotto_usdcearbitrum_response, true);

if ($paygatedotto_usdcearbitrum_conversion_resp && isset($paygatedotto_usdcearbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_usdcearbitrum_final_total = $paygatedotto_usdcearbitrum_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_usdcearbitrum_blockchain_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdc.e/fees.php');


$paygatedotto_usdcearbitrum_blockchain_conversion_resp = json_decode($paygatedotto_usdcearbitrum_blockchain_response, true);

if ($paygatedotto_usdcearbitrum_blockchain_conversion_resp && isset($paygatedotto_usdcearbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_usdcearbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdc.e/convert.php?value=' . $paygatedotto_usdcearbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_usdcearbitrum_conversion_resp = json_decode($paygatedotto_feerevert_usdcearbitrum_response, true);

if ($paygatedotto_feerevert_usdcearbitrum_conversion_resp && isset($paygatedotto_feerevert_usdcearbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_usdcearbitrum_final_total = $paygatedotto_feerevert_usdcearbitrum_conversion_resp['value_coin']; 
// output
    $paygatedotto_usdcearbitrum_blockchain_final_total = $paygatedotto_feerevert_usdcearbitrum_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_usdcearbitrum_amount_to_send = $paygatedotto_usdcearbitrum_final_total + $paygatedotto_usdcearbitrum_blockchain_final_total;		
	
		} else {
	$paygatedotto_usdcearbitrum_amount_to_send = $paygatedotto_usdcearbitrum_final_total;		
		}
		
		
		
$paygatedottocryptogateway_usdcearbitrum_response_minimum = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdc.e/info.php');
$paygatedottocryptogateway_usdcearbitrum_conversion_resp_minimum = json_decode($paygatedottocryptogateway_usdcearbitrum_response_minimum, true);
if ($paygatedottocryptogateway_usdcearbitrum_conversion_resp_minimum && isset($paygatedottocryptogateway_usdcearbitrum_conversion_resp_minimum['minimum'])) {
    $paygatedottocryptogateway_usdcearbitrum_final_total_minimum = $paygatedottocryptogateway_usdcearbitrum_conversion_resp_minimum['minimum'];
    if ($paygatedotto_usdcearbitrum_amount_to_send < $paygatedottocryptogateway_usdcearbitrum_final_total_minimum) {
        return "Error: Payment could not be processed, order total crypto amount to send is less than the minimum allowed for the selected coin";
    }
} else {
    return "Error: Payment could not be processed, can't fetch crypto minimum allowed amount";
}
$paygatedotto_usdcearbitrum_gen_wallet = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdc.e/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_usdcearbitrum_wallet_decbody = json_decode($paygatedotto_usdcearbitrum_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_usdcearbitrum_wallet_decbody && isset($paygatedotto_usdcearbitrum_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_usdcearbitrum_gen_addressIn = $paygatedotto_usdcearbitrum_wallet_decbody['address_in'];
		$paygatedotto_usdcearbitrum_gen_callback = $paygatedotto_usdcearbitrum_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_usdcearbitrum_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_usdcearbitrum_amount_to_send,
'coin_to_send' => 'arbitrum_usdc.e'
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
	
	
        $paygatedotto_usdcearbitrum_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdc.e/qrcode.php?address=' . $paygatedotto_usdcearbitrum_gen_addressIn);


	$paygatedotto_usdcearbitrum_qrcode_decbody = json_decode($paygatedotto_usdcearbitrum_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_usdcearbitrum_qrcode_decbody && isset($paygatedotto_usdcearbitrum_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_usdcearbitrum_gen_qrcode = $paygatedotto_usdcearbitrum_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_usdcearbitrum_gen_qrcode . '" alt="' . $paygatedotto_usdcearbitrum_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_usdcearbitrum_amount_to_send . '</b> arbitrum/usdc.e to the address: <br><b>' . $paygatedotto_usdcearbitrum_gen_addressIn . '</b></div>';
}

function usdcearbitrum_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'usdcearbitrum gateway activated successfully.');
}

function usdcearbitrum_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'usdcearbitrum gateway deactivated successfully.');
}

function usdcearbitrum_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function usdcearbitrum_output($vars)
{
    // Output additional information if needed
}

function usdcearbitrum_error($vars)
{
    // Handle errors if needed
}
