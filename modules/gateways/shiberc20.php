<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function shiberc20_MetaData()
{
    return array(
        'DisplayName' => 'shiberc20',
        'DisableLocalCreditCardInput' => true,
    );
}

function shiberc20_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'SHIBA INU erc20',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto SHIBA INU erc20.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'SHIBA INU erc20 Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your erc20-shib Wallet address.',
        ),
    );
}

function shiberc20_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/shiberc20.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_shiberc20_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_shiberc20_response = file_get_contents('https://api.highriskshop.com/crypto/erc20/shib/convert.php?value=' . $amount . '&from=' . strtolower($hrs_shiberc20_currency));


$hrs_shiberc20_conversion_resp = json_decode($hrs_shiberc20_response, true);

if ($hrs_shiberc20_conversion_resp && isset($hrs_shiberc20_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_shiberc20_final_total = $hrs_shiberc20_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_shiberc20_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/erc20/shib/fees.php');


$hrs_shiberc20_blockchain_conversion_resp = json_decode($hrs_shiberc20_blockchain_response, true);

if ($hrs_shiberc20_blockchain_conversion_resp && isset($hrs_shiberc20_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_shiberc20_response = file_get_contents('https://api.highriskshop.com/crypto/erc20/shib/convert.php?value=' . $hrs_shiberc20_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_shiberc20_conversion_resp = json_decode($hrs_feerevert_shiberc20_response, true);

if ($hrs_feerevert_shiberc20_conversion_resp && isset($hrs_feerevert_shiberc20_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_shiberc20_final_total = $hrs_feerevert_shiberc20_conversion_resp['value_coin']; 
// output
    $hrs_shiberc20_blockchain_final_total = $hrs_feerevert_shiberc20_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_shiberc20_amount_to_send = $hrs_shiberc20_final_total + $hrs_shiberc20_blockchain_final_total;		
	
		} else {
	$hrs_shiberc20_amount_to_send = $hrs_shiberc20_final_total;		
		}
		
		
		
$hrs_shiberc20_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/erc20/shib/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_shiberc20_wallet_decbody = json_decode($hrs_shiberc20_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_shiberc20_wallet_decbody && isset($hrs_shiberc20_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_shiberc20_gen_addressIn = $hrs_shiberc20_wallet_decbody['address_in'];
		$hrs_shiberc20_gen_callback = $hrs_shiberc20_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_shiberc20_gen_addressIn,
'crypto_amount_to_send' => $hrs_shiberc20_amount_to_send,
'coin_to_send' => 'erc20_shib'
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
	
	
        $hrs_shiberc20_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/erc20/shib/qrcode.php?address=' . $hrs_shiberc20_gen_addressIn);


	$hrs_shiberc20_qrcode_decbody = json_decode($hrs_shiberc20_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_shiberc20_qrcode_decbody && isset($hrs_shiberc20_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_shiberc20_gen_qrcode = $hrs_shiberc20_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_shiberc20_gen_qrcode . '" alt="' . $hrs_shiberc20_gen_addressIn . '"></div><div>Please send <b>' . $hrs_shiberc20_amount_to_send . '</b> erc20/shib to the address: <br><b>' . $hrs_shiberc20_gen_addressIn . '</b></div>';
}

function shiberc20_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'shiberc20 gateway activated successfully.');
}

function shiberc20_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'shiberc20 gateway deactivated successfully.');
}

function shiberc20_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function shiberc20_output($vars)
{
    // Output additional information if needed
}

function shiberc20_error($vars)
{
    // Handle errors if needed
}
