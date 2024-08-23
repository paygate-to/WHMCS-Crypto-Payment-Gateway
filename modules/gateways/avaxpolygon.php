<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function avaxpolygon_MetaData()
{
    return array(
        'DisplayName' => 'avaxpolygon',
        'DisableLocalCreditCardInput' => true,
    );
}

function avaxpolygon_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Avalanche Token polygon',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Avalanche Token polygon.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Avalanche Token polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your polygon-avax Wallet address.',
        ),
    );
}

function avaxpolygon_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/avaxpolygon.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_avaxpolygon_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_avaxpolygon_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/avax/convert.php?value=' . $amount . '&from=' . strtolower($hrs_avaxpolygon_currency));


$hrs_avaxpolygon_conversion_resp = json_decode($hrs_avaxpolygon_response, true);

if ($hrs_avaxpolygon_conversion_resp && isset($hrs_avaxpolygon_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_avaxpolygon_final_total = $hrs_avaxpolygon_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_avaxpolygon_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/avax/fees.php');


$hrs_avaxpolygon_blockchain_conversion_resp = json_decode($hrs_avaxpolygon_blockchain_response, true);

if ($hrs_avaxpolygon_blockchain_conversion_resp && isset($hrs_avaxpolygon_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_avaxpolygon_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/avax/convert.php?value=' . $hrs_avaxpolygon_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_avaxpolygon_conversion_resp = json_decode($hrs_feerevert_avaxpolygon_response, true);

if ($hrs_feerevert_avaxpolygon_conversion_resp && isset($hrs_feerevert_avaxpolygon_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_avaxpolygon_final_total = $hrs_feerevert_avaxpolygon_conversion_resp['value_coin']; 
// output
    $hrs_avaxpolygon_blockchain_final_total = $hrs_feerevert_avaxpolygon_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_avaxpolygon_amount_to_send = $hrs_avaxpolygon_final_total + $hrs_avaxpolygon_blockchain_final_total;		
	
		} else {
	$hrs_avaxpolygon_amount_to_send = $hrs_avaxpolygon_final_total;		
		}
		
		
		
$hrs_avaxpolygon_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/polygon/avax/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_avaxpolygon_wallet_decbody = json_decode($hrs_avaxpolygon_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_avaxpolygon_wallet_decbody && isset($hrs_avaxpolygon_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_avaxpolygon_gen_addressIn = $hrs_avaxpolygon_wallet_decbody['address_in'];
		$hrs_avaxpolygon_gen_callback = $hrs_avaxpolygon_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_avaxpolygon_gen_addressIn,
'crypto_amount_to_send' => $hrs_avaxpolygon_amount_to_send,
'coin_to_send' => 'polygon_avax'
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
	
	
        $hrs_avaxpolygon_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/polygon/avax/qrcode.php?address=' . $hrs_avaxpolygon_gen_addressIn);


	$hrs_avaxpolygon_qrcode_decbody = json_decode($hrs_avaxpolygon_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_avaxpolygon_qrcode_decbody && isset($hrs_avaxpolygon_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_avaxpolygon_gen_qrcode = $hrs_avaxpolygon_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_avaxpolygon_gen_qrcode . '" alt="' . $hrs_avaxpolygon_gen_addressIn . '"></div><div>Please send <b>' . $hrs_avaxpolygon_amount_to_send . '</b> polygon/avax to the address: <br><b>' . $hrs_avaxpolygon_gen_addressIn . '</b></div>';
}

function avaxpolygon_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'avaxpolygon gateway activated successfully.');
}

function avaxpolygon_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'avaxpolygon gateway deactivated successfully.');
}

function avaxpolygon_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function avaxpolygon_output($vars)
{
    // Output additional information if needed
}

function avaxpolygon_error($vars)
{
    // Handle errors if needed
}
