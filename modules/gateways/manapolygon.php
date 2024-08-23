<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function manapolygon_MetaData()
{
    return array(
        'DisplayName' => 'manapolygon',
        'DisableLocalCreditCardInput' => true,
    );
}

function manapolygon_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Decentraland polygon',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Decentraland polygon.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Decentraland polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your polygon-mana Wallet address.',
        ),
    );
}

function manapolygon_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/manapolygon.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_manapolygon_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_manapolygon_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/mana/convert.php?value=' . $amount . '&from=' . strtolower($hrs_manapolygon_currency));


$hrs_manapolygon_conversion_resp = json_decode($hrs_manapolygon_response, true);

if ($hrs_manapolygon_conversion_resp && isset($hrs_manapolygon_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_manapolygon_final_total = $hrs_manapolygon_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_manapolygon_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/mana/fees.php');


$hrs_manapolygon_blockchain_conversion_resp = json_decode($hrs_manapolygon_blockchain_response, true);

if ($hrs_manapolygon_blockchain_conversion_resp && isset($hrs_manapolygon_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_manapolygon_response = file_get_contents('https://api.highriskshop.com/crypto/polygon/mana/convert.php?value=' . $hrs_manapolygon_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_manapolygon_conversion_resp = json_decode($hrs_feerevert_manapolygon_response, true);

if ($hrs_feerevert_manapolygon_conversion_resp && isset($hrs_feerevert_manapolygon_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_manapolygon_final_total = $hrs_feerevert_manapolygon_conversion_resp['value_coin']; 
// output
    $hrs_manapolygon_blockchain_final_total = $hrs_feerevert_manapolygon_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_manapolygon_amount_to_send = $hrs_manapolygon_final_total + $hrs_manapolygon_blockchain_final_total;		
	
		} else {
	$hrs_manapolygon_amount_to_send = $hrs_manapolygon_final_total;		
		}
		
		
		
$hrs_manapolygon_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/polygon/mana/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_manapolygon_wallet_decbody = json_decode($hrs_manapolygon_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_manapolygon_wallet_decbody && isset($hrs_manapolygon_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_manapolygon_gen_addressIn = $hrs_manapolygon_wallet_decbody['address_in'];
		$hrs_manapolygon_gen_callback = $hrs_manapolygon_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_manapolygon_gen_addressIn,
'crypto_amount_to_send' => $hrs_manapolygon_amount_to_send,
'coin_to_send' => 'polygon_mana'
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
	
	
        $hrs_manapolygon_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/polygon/mana/qrcode.php?address=' . $hrs_manapolygon_gen_addressIn);


	$hrs_manapolygon_qrcode_decbody = json_decode($hrs_manapolygon_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_manapolygon_qrcode_decbody && isset($hrs_manapolygon_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_manapolygon_gen_qrcode = $hrs_manapolygon_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_manapolygon_gen_qrcode . '" alt="' . $hrs_manapolygon_gen_addressIn . '"></div><div>Please send <b>' . $hrs_manapolygon_amount_to_send . '</b> polygon/mana to the address: <br><b>' . $hrs_manapolygon_gen_addressIn . '</b></div>';
}

function manapolygon_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'manapolygon gateway activated successfully.');
}

function manapolygon_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'manapolygon gateway deactivated successfully.');
}

function manapolygon_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function manapolygon_output($vars)
{
    // Output additional information if needed
}

function manapolygon_error($vars)
{
    // Handle errors if needed
}
