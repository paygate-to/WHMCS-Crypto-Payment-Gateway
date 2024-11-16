<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function usdtarbitrum_MetaData()
{
    return array(
        'DisplayName' => 'usdtarbitrum',
        'DisableLocalCreditCardInput' => true,
    );
}

function usdtarbitrum_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'USDT arbitrum',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto USDT arbitrum.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'USDT arbitrum Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your arbitrum-usdt Wallet address.',
        ),
    );
}

function usdtarbitrum_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/usdtarbitrum.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_usdtarbitrum_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_usdtarbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdt/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_usdtarbitrum_currency));


$paygatedotto_usdtarbitrum_conversion_resp = json_decode($paygatedotto_usdtarbitrum_response, true);

if ($paygatedotto_usdtarbitrum_conversion_resp && isset($paygatedotto_usdtarbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_usdtarbitrum_final_total = $paygatedotto_usdtarbitrum_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_usdtarbitrum_blockchain_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdt/fees.php');


$paygatedotto_usdtarbitrum_blockchain_conversion_resp = json_decode($paygatedotto_usdtarbitrum_blockchain_response, true);

if ($paygatedotto_usdtarbitrum_blockchain_conversion_resp && isset($paygatedotto_usdtarbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_usdtarbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdt/convert.php?value=' . $paygatedotto_usdtarbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_usdtarbitrum_conversion_resp = json_decode($paygatedotto_feerevert_usdtarbitrum_response, true);

if ($paygatedotto_feerevert_usdtarbitrum_conversion_resp && isset($paygatedotto_feerevert_usdtarbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_usdtarbitrum_final_total = $paygatedotto_feerevert_usdtarbitrum_conversion_resp['value_coin']; 
// output
    $paygatedotto_usdtarbitrum_blockchain_final_total = $paygatedotto_feerevert_usdtarbitrum_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_usdtarbitrum_amount_to_send = $paygatedotto_usdtarbitrum_final_total + $paygatedotto_usdtarbitrum_blockchain_final_total;		
	
		} else {
	$paygatedotto_usdtarbitrum_amount_to_send = $paygatedotto_usdtarbitrum_final_total;		
		}
		
		
		
$paygatedotto_usdtarbitrum_gen_wallet = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdt/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_usdtarbitrum_wallet_decbody = json_decode($paygatedotto_usdtarbitrum_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_usdtarbitrum_wallet_decbody && isset($paygatedotto_usdtarbitrum_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_usdtarbitrum_gen_addressIn = $paygatedotto_usdtarbitrum_wallet_decbody['address_in'];
		$paygatedotto_usdtarbitrum_gen_callback = $paygatedotto_usdtarbitrum_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_usdtarbitrum_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_usdtarbitrum_amount_to_send,
'coin_to_send' => 'arbitrum_usdt'
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
	
	
        $paygatedotto_usdtarbitrum_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/arbitrum/usdt/qrcode.php?address=' . $paygatedotto_usdtarbitrum_gen_addressIn);


	$paygatedotto_usdtarbitrum_qrcode_decbody = json_decode($paygatedotto_usdtarbitrum_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_usdtarbitrum_qrcode_decbody && isset($paygatedotto_usdtarbitrum_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_usdtarbitrum_gen_qrcode = $paygatedotto_usdtarbitrum_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_usdtarbitrum_gen_qrcode . '" alt="' . $paygatedotto_usdtarbitrum_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_usdtarbitrum_amount_to_send . '</b> arbitrum/usdt to the address: <br><b>' . $paygatedotto_usdtarbitrum_gen_addressIn . '</b></div>';
}

function usdtarbitrum_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'usdtarbitrum gateway activated successfully.');
}

function usdtarbitrum_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'usdtarbitrum gateway deactivated successfully.');
}

function usdtarbitrum_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function usdtarbitrum_output($vars)
{
    // Output additional information if needed
}

function usdtarbitrum_error($vars)
{
    // Handle errors if needed
}
