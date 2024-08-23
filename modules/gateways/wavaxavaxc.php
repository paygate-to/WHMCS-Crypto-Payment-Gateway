<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function wavaxavaxc_MetaData()
{
    return array(
        'DisplayName' => 'wavaxavaxc',
        'DisableLocalCreditCardInput' => true,
    );
}

function wavaxavaxc_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Wrapped AVAX avax-c',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Wrapped AVAX avax-c.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Wrapped AVAX avax-c Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your avax-c-wavax Wallet address.',
        ),
    );
}

function wavaxavaxc_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/wavaxavaxc.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_wavaxavaxc_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_wavaxavaxc_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/wavax/convert.php?value=' . $amount . '&from=' . strtolower($hrs_wavaxavaxc_currency));


$hrs_wavaxavaxc_conversion_resp = json_decode($hrs_wavaxavaxc_response, true);

if ($hrs_wavaxavaxc_conversion_resp && isset($hrs_wavaxavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_wavaxavaxc_final_total = $hrs_wavaxavaxc_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_wavaxavaxc_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/wavax/fees.php');


$hrs_wavaxavaxc_blockchain_conversion_resp = json_decode($hrs_wavaxavaxc_blockchain_response, true);

if ($hrs_wavaxavaxc_blockchain_conversion_resp && isset($hrs_wavaxavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_wavaxavaxc_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/wavax/convert.php?value=' . $hrs_wavaxavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_wavaxavaxc_conversion_resp = json_decode($hrs_feerevert_wavaxavaxc_response, true);

if ($hrs_feerevert_wavaxavaxc_conversion_resp && isset($hrs_feerevert_wavaxavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_wavaxavaxc_final_total = $hrs_feerevert_wavaxavaxc_conversion_resp['value_coin']; 
// output
    $hrs_wavaxavaxc_blockchain_final_total = $hrs_feerevert_wavaxavaxc_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_wavaxavaxc_amount_to_send = $hrs_wavaxavaxc_final_total + $hrs_wavaxavaxc_blockchain_final_total;		
	
		} else {
	$hrs_wavaxavaxc_amount_to_send = $hrs_wavaxavaxc_final_total;		
		}
		
		
		
$hrs_wavaxavaxc_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/avax-c/wavax/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_wavaxavaxc_wallet_decbody = json_decode($hrs_wavaxavaxc_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_wavaxavaxc_wallet_decbody && isset($hrs_wavaxavaxc_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_wavaxavaxc_gen_addressIn = $hrs_wavaxavaxc_wallet_decbody['address_in'];
		$hrs_wavaxavaxc_gen_callback = $hrs_wavaxavaxc_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_wavaxavaxc_gen_addressIn,
'crypto_amount_to_send' => $hrs_wavaxavaxc_amount_to_send,
'coin_to_send' => 'avax-c_wavax'
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
	
	
        $hrs_wavaxavaxc_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/avax-c/wavax/qrcode.php?address=' . $hrs_wavaxavaxc_gen_addressIn);


	$hrs_wavaxavaxc_qrcode_decbody = json_decode($hrs_wavaxavaxc_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_wavaxavaxc_qrcode_decbody && isset($hrs_wavaxavaxc_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_wavaxavaxc_gen_qrcode = $hrs_wavaxavaxc_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_wavaxavaxc_gen_qrcode . '" alt="' . $hrs_wavaxavaxc_gen_addressIn . '"></div><div>Please send <b>' . $hrs_wavaxavaxc_amount_to_send . '</b> avax-c/wavax to the address: <br><b>' . $hrs_wavaxavaxc_gen_addressIn . '</b></div>';
}

function wavaxavaxc_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'wavaxavaxc gateway activated successfully.');
}

function wavaxavaxc_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'wavaxavaxc gateway deactivated successfully.');
}

function wavaxavaxc_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function wavaxavaxc_output($vars)
{
    // Output additional information if needed
}

function wavaxavaxc_error($vars)
{
    // Handle errors if needed
}
