<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function wethpolygon_MetaData()
{
    return array(
        'DisplayName' => 'wethpolygon',
        'DisableLocalCreditCardInput' => true,
    );
}

function wethpolygon_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Wrapped Ether polygon',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Wrapped Ether polygon.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Wrapped Ether polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your polygon-weth Wallet address.',
        ),
    );
}

function wethpolygon_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/wethpolygon.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_wethpolygon_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_wethpolygon_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/weth/convert.php?value=' . $amount . '&from=' . strtolower($hrs_wethpolygon_currency));


$hrs_wethpolygon_conversion_resp = json_decode($hrs_wethpolygon_response, true);

if ($hrs_wethpolygon_conversion_resp && isset($hrs_wethpolygon_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_wethpolygon_final_total = $hrs_wethpolygon_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_wethpolygon_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/weth/fees.php');


$hrs_wethpolygon_blockchain_conversion_resp = json_decode($hrs_wethpolygon_blockchain_response, true);

if ($hrs_wethpolygon_blockchain_conversion_resp && isset($hrs_wethpolygon_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_wethpolygon_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/weth/convert.php?value=' . $hrs_wethpolygon_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_wethpolygon_conversion_resp = json_decode($hrs_feerevert_wethpolygon_response, true);

if ($hrs_feerevert_wethpolygon_conversion_resp && isset($hrs_feerevert_wethpolygon_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_wethpolygon_final_total = $hrs_feerevert_wethpolygon_conversion_resp['value_coin']; 
// output
    $hrs_wethpolygon_blockchain_final_total = $hrs_feerevert_wethpolygon_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_wethpolygon_amount_to_send = $hrs_wethpolygon_final_total + $hrs_wethpolygon_blockchain_final_total;		
	
		} else {
	$hrs_wethpolygon_amount_to_send = $hrs_wethpolygon_final_total;		
		}
		
		
		
$hrs_wethpolygon_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/polygon/weth/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_wethpolygon_wallet_decbody = json_decode($hrs_wethpolygon_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_wethpolygon_wallet_decbody && isset($hrs_wethpolygon_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_wethpolygon_gen_addressIn = $hrs_wethpolygon_wallet_decbody['address_in'];
		$hrs_wethpolygon_gen_callback = $hrs_wethpolygon_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_wethpolygon_gen_addressIn,
'crypto_amount_to_send' => $hrs_wethpolygon_amount_to_send,
'coin_to_send' => 'polygon_weth'
));

		
		 // Update the invoice description to include address_in
            $invoiceDescription = $hrs_jsonObject;

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $hrs_wethpolygon_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/polygon/weth/qrcode.php?address=' . $hrs_wethpolygon_gen_addressIn);


	$hrs_wethpolygon_qrcode_decbody = json_decode($hrs_wethpolygon_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_wethpolygon_qrcode_decbody && isset($hrs_wethpolygon_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_wethpolygon_gen_qrcode = $hrs_wethpolygon_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_wethpolygon_gen_qrcode . '" alt="' . $hrs_wethpolygon_gen_addressIn . '"></div><div>Please send <b>' . $hrs_wethpolygon_amount_to_send . '</b> polygon/weth to the address: <br><b>' . $hrs_wethpolygon_gen_addressIn . '</b></div>';
}

function wethpolygon_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'wethpolygon gateway activated successfully.');
}

function wethpolygon_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'wethpolygon gateway deactivated successfully.');
}

function wethpolygon_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function wethpolygon_output($vars)
{
    // Output additional information if needed
}

function wethpolygon_error($vars)
{
    // Handle errors if needed
}
