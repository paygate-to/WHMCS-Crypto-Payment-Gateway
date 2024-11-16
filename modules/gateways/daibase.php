<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function daibase_MetaData()
{
    return array(
        'DisplayName' => 'daibase',
        'DisableLocalCreditCardInput' => true,
    );
}

function daibase_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Dai Token base',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Dai Token base.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Dai Token base Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your base-dai Wallet address.',
        ),
    );
}

function daibase_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/daibase.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_daibase_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_daibase_response = file_get_contents('https://api.paygate.to/crypto/base/dai/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_daibase_currency));


$paygatedotto_daibase_conversion_resp = json_decode($paygatedotto_daibase_response, true);

if ($paygatedotto_daibase_conversion_resp && isset($paygatedotto_daibase_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_daibase_final_total = $paygatedotto_daibase_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_daibase_blockchain_response = file_get_contents('https://api.paygate.to/crypto/base/dai/fees.php');


$paygatedotto_daibase_blockchain_conversion_resp = json_decode($paygatedotto_daibase_blockchain_response, true);

if ($paygatedotto_daibase_blockchain_conversion_resp && isset($paygatedotto_daibase_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_daibase_response = file_get_contents('https://api.paygate.to/crypto/base/dai/convert.php?value=' . $paygatedotto_daibase_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_daibase_conversion_resp = json_decode($paygatedotto_feerevert_daibase_response, true);

if ($paygatedotto_feerevert_daibase_conversion_resp && isset($paygatedotto_feerevert_daibase_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_daibase_final_total = $paygatedotto_feerevert_daibase_conversion_resp['value_coin']; 
// output
    $paygatedotto_daibase_blockchain_final_total = $paygatedotto_feerevert_daibase_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_daibase_amount_to_send = $paygatedotto_daibase_final_total + $paygatedotto_daibase_blockchain_final_total;		
	
		} else {
	$paygatedotto_daibase_amount_to_send = $paygatedotto_daibase_final_total;		
		}
		
		
		
$paygatedotto_daibase_gen_wallet = file_get_contents('https://api.paygate.to/crypto/base/dai/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_daibase_wallet_decbody = json_decode($paygatedotto_daibase_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_daibase_wallet_decbody && isset($paygatedotto_daibase_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_daibase_gen_addressIn = $paygatedotto_daibase_wallet_decbody['address_in'];
		$paygatedotto_daibase_gen_callback = $paygatedotto_daibase_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_daibase_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_daibase_amount_to_send,
'coin_to_send' => 'base_dai'
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
	
	
        $paygatedotto_daibase_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/base/dai/qrcode.php?address=' . $paygatedotto_daibase_gen_addressIn);


	$paygatedotto_daibase_qrcode_decbody = json_decode($paygatedotto_daibase_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_daibase_qrcode_decbody && isset($paygatedotto_daibase_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_daibase_gen_qrcode = $paygatedotto_daibase_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_daibase_gen_qrcode . '" alt="' . $paygatedotto_daibase_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_daibase_amount_to_send . '</b> base/dai to the address: <br><b>' . $paygatedotto_daibase_gen_addressIn . '</b></div>';
}

function daibase_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'daibase gateway activated successfully.');
}

function daibase_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'daibase gateway deactivated successfully.');
}

function daibase_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function daibase_output($vars)
{
    // Output additional information if needed
}

function daibase_error($vars)
{
    // Handle errors if needed
}
