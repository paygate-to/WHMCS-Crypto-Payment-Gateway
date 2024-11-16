<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function phptbep20_MetaData()
{
    return array(
        'DisplayName' => 'phptbep20',
        'DisableLocalCreditCardInput' => true,
    );
}

function phptbep20_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'PHPt bep20',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto PHPt bep20.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'wallet_address' => array(
            'FriendlyName' => 'PHPt bep20 Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your bep20-phpt Wallet address.',
        ),
    );
}

function phptbep20_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/phptbep20.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_phptbep20_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_phptbep20_response = file_get_contents('https://api.paygate.to/crypto/bep20/phpt/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_phptbep20_currency));


$paygatedotto_phptbep20_conversion_resp = json_decode($paygatedotto_phptbep20_response, true);

if ($paygatedotto_phptbep20_conversion_resp && isset($paygatedotto_phptbep20_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_phptbep20_final_total = $paygatedotto_phptbep20_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_phptbep20_blockchain_response = file_get_contents('https://api.paygate.to/crypto/bep20/phpt/fees.php');


$paygatedotto_phptbep20_blockchain_conversion_resp = json_decode($paygatedotto_phptbep20_blockchain_response, true);

if ($paygatedotto_phptbep20_blockchain_conversion_resp && isset($paygatedotto_phptbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_phptbep20_response = file_get_contents('https://api.paygate.to/crypto/bep20/phpt/convert.php?value=' . $paygatedotto_phptbep20_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_phptbep20_conversion_resp = json_decode($paygatedotto_feerevert_phptbep20_response, true);

if ($paygatedotto_feerevert_phptbep20_conversion_resp && isset($paygatedotto_feerevert_phptbep20_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_phptbep20_final_total = $paygatedotto_feerevert_phptbep20_conversion_resp['value_coin']; 
// output
    $paygatedotto_phptbep20_blockchain_final_total = $paygatedotto_feerevert_phptbep20_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_phptbep20_amount_to_send = $paygatedotto_phptbep20_final_total + $paygatedotto_phptbep20_blockchain_final_total;		
	
		} else {
	$paygatedotto_phptbep20_amount_to_send = $paygatedotto_phptbep20_final_total;		
		}
		
		
		
$paygatedotto_phptbep20_gen_wallet = file_get_contents('https://api.paygate.to/crypto/bep20/phpt/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_phptbep20_wallet_decbody = json_decode($paygatedotto_phptbep20_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_phptbep20_wallet_decbody && isset($paygatedotto_phptbep20_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_phptbep20_gen_addressIn = $paygatedotto_phptbep20_wallet_decbody['address_in'];
		$paygatedotto_phptbep20_gen_callback = $paygatedotto_phptbep20_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_phptbep20_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_phptbep20_amount_to_send,
'coin_to_send' => 'bep20_phpt'
));

		
		 // Update the invoice description to include address_in
            $invoiceDescription = $paygatedotto_jsonObject;

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paygatedotto_phptbep20_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/bep20/phpt/qrcode.php?address=' . $paygatedotto_phptbep20_gen_addressIn);


	$paygatedotto_phptbep20_qrcode_decbody = json_decode($paygatedotto_phptbep20_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_phptbep20_qrcode_decbody && isset($paygatedotto_phptbep20_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_phptbep20_gen_qrcode = $paygatedotto_phptbep20_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_phptbep20_gen_qrcode . '" alt="' . $paygatedotto_phptbep20_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_phptbep20_amount_to_send . '</b> bep20/phpt to the address: <br><b>' . $paygatedotto_phptbep20_gen_addressIn . '</b></div>';
}

function phptbep20_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'phptbep20 gateway activated successfully.');
}

function phptbep20_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'phptbep20 gateway deactivated successfully.');
}

function phptbep20_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function phptbep20_output($vars)
{
    // Output additional information if needed
}

function phptbep20_error($vars)
{
    // Handle errors if needed
}
