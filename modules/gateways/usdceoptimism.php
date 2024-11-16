<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function usdceoptimism_MetaData()
{
    return array(
        'DisplayName' => 'usdceoptimism',
        'DisableLocalCreditCardInput' => true,
    );
}

function usdceoptimism_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'USD Coin (Bridged) optimism',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto USD Coin (Bridged) optimism.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'USD Coin (Bridged) optimism Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your optimism-usdc.e Wallet address.',
        ),
    );
}

function usdceoptimism_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/usdceoptimism.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_usdceoptimism_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_usdceoptimism_response = file_get_contents('https://api.paygate.to/crypto/optimism/usdc.e/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_usdceoptimism_currency));


$paygatedotto_usdceoptimism_conversion_resp = json_decode($paygatedotto_usdceoptimism_response, true);

if ($paygatedotto_usdceoptimism_conversion_resp && isset($paygatedotto_usdceoptimism_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_usdceoptimism_final_total = $paygatedotto_usdceoptimism_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_usdceoptimism_blockchain_response = file_get_contents('https://api.paygate.to/crypto/optimism/usdc.e/fees.php');


$paygatedotto_usdceoptimism_blockchain_conversion_resp = json_decode($paygatedotto_usdceoptimism_blockchain_response, true);

if ($paygatedotto_usdceoptimism_blockchain_conversion_resp && isset($paygatedotto_usdceoptimism_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_usdceoptimism_response = file_get_contents('https://api.paygate.to/crypto/optimism/usdc.e/convert.php?value=' . $paygatedotto_usdceoptimism_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_usdceoptimism_conversion_resp = json_decode($paygatedotto_feerevert_usdceoptimism_response, true);

if ($paygatedotto_feerevert_usdceoptimism_conversion_resp && isset($paygatedotto_feerevert_usdceoptimism_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_usdceoptimism_final_total = $paygatedotto_feerevert_usdceoptimism_conversion_resp['value_coin']; 
// output
    $paygatedotto_usdceoptimism_blockchain_final_total = $paygatedotto_feerevert_usdceoptimism_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_usdceoptimism_amount_to_send = $paygatedotto_usdceoptimism_final_total + $paygatedotto_usdceoptimism_blockchain_final_total;		
	
		} else {
	$paygatedotto_usdceoptimism_amount_to_send = $paygatedotto_usdceoptimism_final_total;		
		}
		
		
		
$paygatedotto_usdceoptimism_gen_wallet = file_get_contents('https://api.paygate.to/crypto/optimism/usdc.e/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_usdceoptimism_wallet_decbody = json_decode($paygatedotto_usdceoptimism_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_usdceoptimism_wallet_decbody && isset($paygatedotto_usdceoptimism_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_usdceoptimism_gen_addressIn = $paygatedotto_usdceoptimism_wallet_decbody['address_in'];
		$paygatedotto_usdceoptimism_gen_callback = $paygatedotto_usdceoptimism_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_usdceoptimism_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_usdceoptimism_amount_to_send,
'coin_to_send' => 'optimism_usdc.e'
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
	
	
        $paygatedotto_usdceoptimism_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/optimism/usdc.e/qrcode.php?address=' . $paygatedotto_usdceoptimism_gen_addressIn);


	$paygatedotto_usdceoptimism_qrcode_decbody = json_decode($paygatedotto_usdceoptimism_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_usdceoptimism_qrcode_decbody && isset($paygatedotto_usdceoptimism_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_usdceoptimism_gen_qrcode = $paygatedotto_usdceoptimism_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_usdceoptimism_gen_qrcode . '" alt="' . $paygatedotto_usdceoptimism_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_usdceoptimism_amount_to_send . '</b> optimism/usdc.e to the address: <br><b>' . $paygatedotto_usdceoptimism_gen_addressIn . '</b></div>';
}

function usdceoptimism_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'usdceoptimism gateway activated successfully.');
}

function usdceoptimism_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'usdceoptimism gateway deactivated successfully.');
}

function usdceoptimism_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function usdceoptimism_output($vars)
{
    // Output additional information if needed
}

function usdceoptimism_error($vars)
{
    // Handle errors if needed
}
