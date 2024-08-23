<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function wbtcoptimism_MetaData()
{
    return array(
        'DisplayName' => 'wbtcoptimism',
        'DisableLocalCreditCardInput' => true,
    );
}

function wbtcoptimism_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Wrapped BTC optimism',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Wrapped BTC optimism.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Wrapped BTC optimism Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your optimism-wbtc Wallet address.',
        ),
    );
}

function wbtcoptimism_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/wbtcoptimism.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_wbtcoptimism_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_wbtcoptimism_response = file_get_contents('https://api.highriskshop.com/crypto/optimism/wbtc/convert.php?value=' . $amount . '&from=' . strtolower($hrs_wbtcoptimism_currency));


$hrs_wbtcoptimism_conversion_resp = json_decode($hrs_wbtcoptimism_response, true);

if ($hrs_wbtcoptimism_conversion_resp && isset($hrs_wbtcoptimism_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_wbtcoptimism_final_total = $hrs_wbtcoptimism_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_wbtcoptimism_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/optimism/wbtc/fees.php');


$hrs_wbtcoptimism_blockchain_conversion_resp = json_decode($hrs_wbtcoptimism_blockchain_response, true);

if ($hrs_wbtcoptimism_blockchain_conversion_resp && isset($hrs_wbtcoptimism_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_wbtcoptimism_response = file_get_contents('https://api.highriskshop.com/crypto/optimism/wbtc/convert.php?value=' . $hrs_wbtcoptimism_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_wbtcoptimism_conversion_resp = json_decode($hrs_feerevert_wbtcoptimism_response, true);

if ($hrs_feerevert_wbtcoptimism_conversion_resp && isset($hrs_feerevert_wbtcoptimism_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_wbtcoptimism_final_total = $hrs_feerevert_wbtcoptimism_conversion_resp['value_coin']; 
// output
    $hrs_wbtcoptimism_blockchain_final_total = $hrs_feerevert_wbtcoptimism_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_wbtcoptimism_amount_to_send = $hrs_wbtcoptimism_final_total + $hrs_wbtcoptimism_blockchain_final_total;		
	
		} else {
	$hrs_wbtcoptimism_amount_to_send = $hrs_wbtcoptimism_final_total;		
		}
		
		
		
$hrs_wbtcoptimism_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/optimism/wbtc/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_wbtcoptimism_wallet_decbody = json_decode($hrs_wbtcoptimism_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_wbtcoptimism_wallet_decbody && isset($hrs_wbtcoptimism_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_wbtcoptimism_gen_addressIn = $hrs_wbtcoptimism_wallet_decbody['address_in'];
		$hrs_wbtcoptimism_gen_callback = $hrs_wbtcoptimism_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_wbtcoptimism_gen_addressIn,
'crypto_amount_to_send' => $hrs_wbtcoptimism_amount_to_send,
'coin_to_send' => 'optimism_wbtc'
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
	
	
        $hrs_wbtcoptimism_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/optimism/wbtc/qrcode.php?address=' . $hrs_wbtcoptimism_gen_addressIn);


	$hrs_wbtcoptimism_qrcode_decbody = json_decode($hrs_wbtcoptimism_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_wbtcoptimism_qrcode_decbody && isset($hrs_wbtcoptimism_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_wbtcoptimism_gen_qrcode = $hrs_wbtcoptimism_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_wbtcoptimism_gen_qrcode . '" alt="' . $hrs_wbtcoptimism_gen_addressIn . '"></div><div>Please send <b>' . $hrs_wbtcoptimism_amount_to_send . '</b> optimism/wbtc to the address: <br><b>' . $hrs_wbtcoptimism_gen_addressIn . '</b></div>';
}

function wbtcoptimism_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'wbtcoptimism gateway activated successfully.');
}

function wbtcoptimism_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'wbtcoptimism gateway deactivated successfully.');
}

function wbtcoptimism_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function wbtcoptimism_output($vars)
{
    // Output additional information if needed
}

function wbtcoptimism_error($vars)
{
    // Handle errors if needed
}
