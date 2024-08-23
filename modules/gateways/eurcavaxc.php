<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function eurcavaxc_MetaData()
{
    return array(
        'DisplayName' => 'eurcavaxc',
        'DisableLocalCreditCardInput' => true,
    );
}

function eurcavaxc_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'EURC avax-c',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto EURC avax-c.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'EURC avax-c Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your avax-c-eurc Wallet address.',
        ),
    );
}

function eurcavaxc_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/eurcavaxc.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_eurcavaxc_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_eurcavaxc_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/eurc/convert.php?value=' . $amount . '&from=' . strtolower($hrs_eurcavaxc_currency));


$hrs_eurcavaxc_conversion_resp = json_decode($hrs_eurcavaxc_response, true);

if ($hrs_eurcavaxc_conversion_resp && isset($hrs_eurcavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_eurcavaxc_final_total = $hrs_eurcavaxc_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_eurcavaxc_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/eurc/fees.php');


$hrs_eurcavaxc_blockchain_conversion_resp = json_decode($hrs_eurcavaxc_blockchain_response, true);

if ($hrs_eurcavaxc_blockchain_conversion_resp && isset($hrs_eurcavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_eurcavaxc_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/eurc/convert.php?value=' . $hrs_eurcavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_eurcavaxc_conversion_resp = json_decode($hrs_feerevert_eurcavaxc_response, true);

if ($hrs_feerevert_eurcavaxc_conversion_resp && isset($hrs_feerevert_eurcavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_eurcavaxc_final_total = $hrs_feerevert_eurcavaxc_conversion_resp['value_coin']; 
// output
    $hrs_eurcavaxc_blockchain_final_total = $hrs_feerevert_eurcavaxc_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_eurcavaxc_amount_to_send = $hrs_eurcavaxc_final_total + $hrs_eurcavaxc_blockchain_final_total;		
	
		} else {
	$hrs_eurcavaxc_amount_to_send = $hrs_eurcavaxc_final_total;		
		}
		
		
		
$hrs_eurcavaxc_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/avax-c/eurc/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_eurcavaxc_wallet_decbody = json_decode($hrs_eurcavaxc_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_eurcavaxc_wallet_decbody && isset($hrs_eurcavaxc_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_eurcavaxc_gen_addressIn = $hrs_eurcavaxc_wallet_decbody['address_in'];
		$hrs_eurcavaxc_gen_callback = $hrs_eurcavaxc_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_eurcavaxc_gen_addressIn,
'crypto_amount_to_send' => $hrs_eurcavaxc_amount_to_send,
'coin_to_send' => 'avax-c_eurc'
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
	
	
        $hrs_eurcavaxc_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/avax-c/eurc/qrcode.php?address=' . $hrs_eurcavaxc_gen_addressIn);


	$hrs_eurcavaxc_qrcode_decbody = json_decode($hrs_eurcavaxc_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_eurcavaxc_qrcode_decbody && isset($hrs_eurcavaxc_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_eurcavaxc_gen_qrcode = $hrs_eurcavaxc_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_eurcavaxc_gen_qrcode . '" alt="' . $hrs_eurcavaxc_gen_addressIn . '"></div><div>Please send <b>' . $hrs_eurcavaxc_amount_to_send . '</b> avax-c/eurc to the address: <br><b>' . $hrs_eurcavaxc_gen_addressIn . '</b></div>';
}

function eurcavaxc_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'eurcavaxc gateway activated successfully.');
}

function eurcavaxc_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'eurcavaxc gateway deactivated successfully.');
}

function eurcavaxc_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function eurcavaxc_output($vars)
{
    // Output additional information if needed
}

function eurcavaxc_error($vars)
{
    // Handle errors if needed
}