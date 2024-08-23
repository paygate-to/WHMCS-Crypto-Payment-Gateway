<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function bnbbep20_MetaData()
{
    return array(
        'DisplayName' => 'bnbbep20',
        'DisableLocalCreditCardInput' => true,
    );
}

function bnbbep20_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'BNB bep20',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto BNB bep20.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'BNB bep20 Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your bep20-bnb Wallet address.',
        ),
    );
}

function bnbbep20_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/bnbbep20.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_bnbbep20_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_bnbbep20_response = file_get_contents('https://api.highriskshop.com/crypto/bep20/bnb/convert.php?value=' . $amount . '&from=' . strtolower($hrs_bnbbep20_currency));


$hrs_bnbbep20_conversion_resp = json_decode($hrs_bnbbep20_response, true);

if ($hrs_bnbbep20_conversion_resp && isset($hrs_bnbbep20_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_bnbbep20_final_total = $hrs_bnbbep20_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_bnbbep20_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/bep20/bnb/fees.php');


$hrs_bnbbep20_blockchain_conversion_resp = json_decode($hrs_bnbbep20_blockchain_response, true);

if ($hrs_bnbbep20_blockchain_conversion_resp && isset($hrs_bnbbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_bnbbep20_response = file_get_contents('https://api.highriskshop.com/crypto/bep20/bnb/convert.php?value=' . $hrs_bnbbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_bnbbep20_conversion_resp = json_decode($hrs_feerevert_bnbbep20_response, true);

if ($hrs_feerevert_bnbbep20_conversion_resp && isset($hrs_feerevert_bnbbep20_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_bnbbep20_final_total = $hrs_feerevert_bnbbep20_conversion_resp['value_coin']; 
// output
    $hrs_bnbbep20_blockchain_final_total = $hrs_feerevert_bnbbep20_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_bnbbep20_amount_to_send = $hrs_bnbbep20_final_total + $hrs_bnbbep20_blockchain_final_total;		
	
		} else {
	$hrs_bnbbep20_amount_to_send = $hrs_bnbbep20_final_total;		
		}
		
		
		
$hrs_bnbbep20_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/bep20/bnb/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_bnbbep20_wallet_decbody = json_decode($hrs_bnbbep20_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_bnbbep20_wallet_decbody && isset($hrs_bnbbep20_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_bnbbep20_gen_addressIn = $hrs_bnbbep20_wallet_decbody['address_in'];
		$hrs_bnbbep20_gen_callback = $hrs_bnbbep20_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_bnbbep20_gen_addressIn,
'crypto_amount_to_send' => $hrs_bnbbep20_amount_to_send,
'coin_to_send' => 'bep20_bnb'
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
	
	
        $hrs_bnbbep20_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/bep20/bnb/qrcode.php?address=' . $hrs_bnbbep20_gen_addressIn);


	$hrs_bnbbep20_qrcode_decbody = json_decode($hrs_bnbbep20_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_bnbbep20_qrcode_decbody && isset($hrs_bnbbep20_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_bnbbep20_gen_qrcode = $hrs_bnbbep20_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_bnbbep20_gen_qrcode . '" alt="' . $hrs_bnbbep20_gen_addressIn . '"></div><div>Please send <b>' . $hrs_bnbbep20_amount_to_send . '</b> bep20/bnb to the address: <br><b>' . $hrs_bnbbep20_gen_addressIn . '</b></div>';
}

function bnbbep20_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'bnbbep20 gateway activated successfully.');
}

function bnbbep20_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'bnbbep20 gateway deactivated successfully.');
}

function bnbbep20_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function bnbbep20_output($vars)
{
    // Output additional information if needed
}

function bnbbep20_error($vars)
{
    // Handle errors if needed
}
