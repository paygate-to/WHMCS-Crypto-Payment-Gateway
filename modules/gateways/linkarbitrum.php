<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function linkarbitrum_MetaData()
{
    return array(
        'DisplayName' => 'linkarbitrum',
        'DisableLocalCreditCardInput' => true,
    );
}

function linkarbitrum_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'ChainLink Token arbitrum',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto ChainLink Token arbitrum.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'ChainLink Token arbitrum Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your arbitrum-link Wallet address.',
        ),
    );
}

function linkarbitrum_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/linkarbitrum.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_linkarbitrum_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_linkarbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/link/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_linkarbitrum_currency));


$paygatedotto_linkarbitrum_conversion_resp = json_decode($paygatedotto_linkarbitrum_response, true);

if ($paygatedotto_linkarbitrum_conversion_resp && isset($paygatedotto_linkarbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_linkarbitrum_final_total = $paygatedotto_linkarbitrum_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_linkarbitrum_blockchain_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/link/fees.php');


$paygatedotto_linkarbitrum_blockchain_conversion_resp = json_decode($paygatedotto_linkarbitrum_blockchain_response, true);

if ($paygatedotto_linkarbitrum_blockchain_conversion_resp && isset($paygatedotto_linkarbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_linkarbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/link/convert.php?value=' . $paygatedotto_linkarbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_linkarbitrum_conversion_resp = json_decode($paygatedotto_feerevert_linkarbitrum_response, true);

if ($paygatedotto_feerevert_linkarbitrum_conversion_resp && isset($paygatedotto_feerevert_linkarbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_linkarbitrum_final_total = $paygatedotto_feerevert_linkarbitrum_conversion_resp['value_coin']; 
// output
    $paygatedotto_linkarbitrum_blockchain_final_total = $paygatedotto_feerevert_linkarbitrum_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_linkarbitrum_amount_to_send = $paygatedotto_linkarbitrum_final_total + $paygatedotto_linkarbitrum_blockchain_final_total;		
	
		} else {
	$paygatedotto_linkarbitrum_amount_to_send = $paygatedotto_linkarbitrum_final_total;		
		}
		
		
		
$paygatedotto_linkarbitrum_gen_wallet = file_get_contents('https://api.paygate.to/crypto/arbitrum/link/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_linkarbitrum_wallet_decbody = json_decode($paygatedotto_linkarbitrum_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_linkarbitrum_wallet_decbody && isset($paygatedotto_linkarbitrum_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_linkarbitrum_gen_addressIn = $paygatedotto_linkarbitrum_wallet_decbody['address_in'];
		$paygatedotto_linkarbitrum_gen_callback = $paygatedotto_linkarbitrum_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_linkarbitrum_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_linkarbitrum_amount_to_send,
'coin_to_send' => 'arbitrum_link'
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
	
	
        $paygatedotto_linkarbitrum_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/arbitrum/link/qrcode.php?address=' . $paygatedotto_linkarbitrum_gen_addressIn);


	$paygatedotto_linkarbitrum_qrcode_decbody = json_decode($paygatedotto_linkarbitrum_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_linkarbitrum_qrcode_decbody && isset($paygatedotto_linkarbitrum_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_linkarbitrum_gen_qrcode = $paygatedotto_linkarbitrum_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_linkarbitrum_gen_qrcode . '" alt="' . $paygatedotto_linkarbitrum_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_linkarbitrum_amount_to_send . '</b> arbitrum/link to the address: <br><b>' . $paygatedotto_linkarbitrum_gen_addressIn . '</b></div>';
}

function linkarbitrum_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'linkarbitrum gateway activated successfully.');
}

function linkarbitrum_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'linkarbitrum gateway deactivated successfully.');
}

function linkarbitrum_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function linkarbitrum_output($vars)
{
    // Output additional information if needed
}

function linkarbitrum_error($vars)
{
    // Handle errors if needed
}
