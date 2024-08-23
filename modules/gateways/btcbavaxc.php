<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function btcbavaxc_MetaData()
{
    return array(
        'DisplayName' => 'btcbavaxc',
        'DisableLocalCreditCardInput' => true,
    );
}

function btcbavaxc_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Bitcoin avax-c',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Bitcoin avax-c.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Bitcoin avax-c Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your avax-c-btc.b Wallet address.',
        ),
    );
}

function btcbavaxc_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/btcbavaxc.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_btcbavaxc_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_btcbavaxc_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/btc.b/convert.php?value=' . $amount . '&from=' . strtolower($hrs_btcbavaxc_currency));


$hrs_btcbavaxc_conversion_resp = json_decode($hrs_btcbavaxc_response, true);

if ($hrs_btcbavaxc_conversion_resp && isset($hrs_btcbavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_btcbavaxc_final_total = $hrs_btcbavaxc_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_btcbavaxc_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/btc.b/fees.php');


$hrs_btcbavaxc_blockchain_conversion_resp = json_decode($hrs_btcbavaxc_blockchain_response, true);

if ($hrs_btcbavaxc_blockchain_conversion_resp && isset($hrs_btcbavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_btcbavaxc_response = file_get_contents('https://api.highriskshop.com/crypto/avax-c/btc.b/convert.php?value=' . $hrs_btcbavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_btcbavaxc_conversion_resp = json_decode($hrs_feerevert_btcbavaxc_response, true);

if ($hrs_feerevert_btcbavaxc_conversion_resp && isset($hrs_feerevert_btcbavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_btcbavaxc_final_total = $hrs_feerevert_btcbavaxc_conversion_resp['value_coin']; 
// output
    $hrs_btcbavaxc_blockchain_final_total = $hrs_feerevert_btcbavaxc_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_btcbavaxc_amount_to_send = $hrs_btcbavaxc_final_total + $hrs_btcbavaxc_blockchain_final_total;		
	
		} else {
	$hrs_btcbavaxc_amount_to_send = $hrs_btcbavaxc_final_total;		
		}
		
		
		
$hrs_btcbavaxc_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/avax-c/btc.b/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_btcbavaxc_wallet_decbody = json_decode($hrs_btcbavaxc_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_btcbavaxc_wallet_decbody && isset($hrs_btcbavaxc_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_btcbavaxc_gen_addressIn = $hrs_btcbavaxc_wallet_decbody['address_in'];
		$hrs_btcbavaxc_gen_callback = $hrs_btcbavaxc_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_btcbavaxc_gen_addressIn,
'crypto_amount_to_send' => $hrs_btcbavaxc_amount_to_send,
'coin_to_send' => 'avax-c_btc.b'
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
	
	
        $hrs_btcbavaxc_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/avax-c/btc.b/qrcode.php?address=' . $hrs_btcbavaxc_gen_addressIn);


	$hrs_btcbavaxc_qrcode_decbody = json_decode($hrs_btcbavaxc_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_btcbavaxc_qrcode_decbody && isset($hrs_btcbavaxc_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_btcbavaxc_gen_qrcode = $hrs_btcbavaxc_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_btcbavaxc_gen_qrcode . '" alt="' . $hrs_btcbavaxc_gen_addressIn . '"></div><div>Please send <b>' . $hrs_btcbavaxc_amount_to_send . '</b> avax-c/btc.b to the address: <br><b>' . $hrs_btcbavaxc_gen_addressIn . '</b></div>';
}

function btcbavaxc_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'btcbavaxc gateway activated successfully.');
}

function btcbavaxc_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'btcbavaxc gateway deactivated successfully.');
}

function btcbavaxc_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function btcbavaxc_output($vars)
{
    // Output additional information if needed
}

function btcbavaxc_error($vars)
{
    // Handle errors if needed
}
