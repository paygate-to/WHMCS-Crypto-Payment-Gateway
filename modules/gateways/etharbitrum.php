<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function etharbitrum_MetaData()
{
    return array(
        'DisplayName' => 'etharbitrum',
        'DisableLocalCreditCardInput' => true,
    );
}

function etharbitrum_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Ethereum arbitrum',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Ethereum arbitrum.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'Ethereum arbitrum Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your arbitrum-eth Wallet address.',
        ),
    );
}

function etharbitrum_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/etharbitrum.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$hrs_etharbitrum_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$hrs_etharbitrum_response = file_get_contents('https://api.highriskshop.com/crypto/arbitrum/eth/convert.php?value=' . $amount . '&from=' . strtolower($hrs_etharbitrum_currency));


$hrs_etharbitrum_conversion_resp = json_decode($hrs_etharbitrum_response, true);

if ($hrs_etharbitrum_conversion_resp && isset($hrs_etharbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_etharbitrum_final_total = $hrs_etharbitrum_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$hrs_etharbitrum_blockchain_response = file_get_contents('https://api.highriskshop.com/crypto/arbitrum/eth/fees.php');


$hrs_etharbitrum_blockchain_conversion_resp = json_decode($hrs_etharbitrum_blockchain_response, true);

if ($hrs_etharbitrum_blockchain_conversion_resp && isset($hrs_etharbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$hrs_feerevert_etharbitrum_response = file_get_contents('https://api.highriskshop.com/crypto/arbitrum/eth/convert.php?value=' . $hrs_etharbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$hrs_feerevert_etharbitrum_conversion_resp = json_decode($hrs_feerevert_etharbitrum_response, true);

if ($hrs_feerevert_etharbitrum_conversion_resp && isset($hrs_feerevert_etharbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $hrs_feerevert_etharbitrum_final_total = $hrs_feerevert_etharbitrum_conversion_resp['value_coin']; 
// output
    $hrs_etharbitrum_blockchain_final_total = $hrs_feerevert_etharbitrum_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $hrs_etharbitrum_amount_to_send = $hrs_etharbitrum_final_total + $hrs_etharbitrum_blockchain_final_total;		
	
		} else {
	$hrs_etharbitrum_amount_to_send = $hrs_etharbitrum_final_total;		
		}
		
		
		
$hrs_etharbitrum_gen_wallet = file_get_contents('https://api.highriskshop.com/crypto/arbitrum/eth/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$hrs_etharbitrum_wallet_decbody = json_decode($hrs_etharbitrum_gen_wallet, true);

 // Check if decoding was successful
    if ($hrs_etharbitrum_wallet_decbody && isset($hrs_etharbitrum_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $hrs_etharbitrum_gen_addressIn = $hrs_etharbitrum_wallet_decbody['address_in'];
		$hrs_etharbitrum_gen_callback = $hrs_etharbitrum_wallet_decbody['callback_url'];
		
		$hrs_jsonObject = json_encode(array(
'pay_to_address' => $hrs_etharbitrum_gen_addressIn,
'crypto_amount_to_send' => $hrs_etharbitrum_amount_to_send,
'coin_to_send' => 'arbitrum_eth'
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
	
	
        $hrs_etharbitrum_gen_qrcode = file_get_contents('https://api.highriskshop.com/crypto/arbitrum/eth/qrcode.php?address=' . $hrs_etharbitrum_gen_addressIn);


	$hrs_etharbitrum_qrcode_decbody = json_decode($hrs_etharbitrum_gen_qrcode, true);

 // Check if decoding was successful
    if ($hrs_etharbitrum_qrcode_decbody && isset($hrs_etharbitrum_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $hrs_etharbitrum_gen_qrcode = $hrs_etharbitrum_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $hrs_etharbitrum_gen_qrcode . '" alt="' . $hrs_etharbitrum_gen_addressIn . '"></div><div>Please send <b>' . $hrs_etharbitrum_amount_to_send . '</b> arbitrum/eth to the address: <br><b>' . $hrs_etharbitrum_gen_addressIn . '</b></div>';
}

function etharbitrum_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'etharbitrum gateway activated successfully.');
}

function etharbitrum_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'etharbitrum gateway deactivated successfully.');
}

function etharbitrum_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function etharbitrum_output($vars)
{
    // Output additional information if needed
}

function etharbitrum_error($vars)
{
    // Handle errors if needed
}
