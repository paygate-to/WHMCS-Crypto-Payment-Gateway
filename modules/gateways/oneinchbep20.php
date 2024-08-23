<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function oneinchbep20_MetaData()
{
    return array(
        'DisplayName' => 'oneinchbep20',
        'DisableLocalCreditCardInput' => true,
    );
}

function oneinchbep20_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => '1INCH Token bep20',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto 1INCH Token bep20.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => '1INCH Token bep20 Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your bep20-1inch Wallet address.',
        ),
    );
}

function oneinchbep20_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/oneinchbep20.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_oneinchbep20_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_oneinchbep20_response = file_get_contents('https://api.highriskshop.com/crypto/bep20/1inch/convert.php?value=' . $amount . '&from=' . strtolower($hrs_oneinchbep20_currency));


$hrs_oneinchbep20_conversion_resp = json_decode($hrs_oneinchbep20_response, true);

if ($hrs_oneinchbep20_conversion_resp && isset($hrs_oneinchbep20_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_oneinchbep20_final_total = $hrs_oneinchbep20_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_oneinchbep20_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/bep20/1inch/fees.php');


$hrs_oneinchbep20_blockchain_conversion_resp = json_decode($hrs_oneinchbep20_blockchain_response, true);

if ($hrs_oneinchbep20_blockchain_conversion_resp && isset($hrs_oneinchbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_oneinchbep20_response = file_get_contents('https://api.highriskshop.com/crypto/bep20/1inch/convert.php?value=' . $hrs_oneinchbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_oneinchbep20_conversion_resp = json_decode($hrs_feerevert_oneinchbep20_response, true);

if ($hrs_feerevert_oneinchbep20_conversion_resp && isset($hrs_feerevert_oneinchbep20_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_oneinchbep20_final_total = $hrs_feerevert_oneinchbep20_conversion_resp['value_coin']; 
// output
    $hrs_oneinchbep20_blockchain_final_total = $hrs_feerevert_oneinchbep20_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_oneinchbep20_amount_to_send = $hrs_oneinchbep20_final_total + $hrs_oneinchbep20_blockchain_final_total;		
	
		} else {
	$hrs_oneinchbep20_amount_to_send = $hrs_oneinchbep20_final_total;		
		}
		
		
		
$hrs_oneinchbep20_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/bep20/1inch/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_oneinchbep20_wallet_decbody = json_decode($hrs_oneinchbep20_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_oneinchbep20_wallet_decbody && isset($hrs_oneinchbep20_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_oneinchbep20_gen_addressIn = $hrs_oneinchbep20_wallet_decbody['address_in'];
		$hrs_oneinchbep20_gen_callback = $hrs_oneinchbep20_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_oneinchbep20_gen_addressIn,
'crypto_amount_to_send' => $hrs_oneinchbep20_amount_to_send,
'coin_to_send' => 'bep20_1inch'
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
	
	
        $hrs_oneinchbep20_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/bep20/1inch/qrcode.php?address=' . $hrs_oneinchbep20_gen_addressIn);


	$hrs_oneinchbep20_qrcode_decbody = json_decode($hrs_oneinchbep20_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_oneinchbep20_qrcode_decbody && isset($hrs_oneinchbep20_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_oneinchbep20_gen_qrcode = $hrs_oneinchbep20_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_oneinchbep20_gen_qrcode . '" alt="' . $hrs_oneinchbep20_gen_addressIn . '"></div><div>Please send <b>' . $hrs_oneinchbep20_amount_to_send . '</b> bep20/1inch to the address: <br><b>' . $hrs_oneinchbep20_gen_addressIn . '</b></div>';
}

function oneinchbep20_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'oneinchbep20 gateway activated successfully.');
}

function oneinchbep20_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'oneinchbep20 gateway deactivated successfully.');
}

function oneinchbep20_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function oneinchbep20_output($vars)
{
    // Output additional information if needed
}

function oneinchbep20_error($vars)
{
    // Handle errors if needed
}