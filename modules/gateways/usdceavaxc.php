<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function usdceavaxc_MetaData()
{
    return array(
        'DisplayName' => 'usdceavaxc',
        'DisableLocalCreditCardInput' => true,
    );
}

function usdceavaxc_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'USD Coin (Bridged) avax-c',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto USD Coin (Bridged) avax-c.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'blockchain_fees' => array(
            'FriendlyName' => 'Customer pays blockchain fees',
            'Type' => 'yesno',
            'Description' => 'Add estimate blockchain fees to the invoice total.',
            'Default' => 'off',
        ),		
        'underpaid_tolerance' => array(
            'FriendlyName' => 'Underpaid Tolerance',
            'Type' => 'dropdown',
            'Options' => [
                '1' => '0%',
                '0.99' => '1%',
                '0.98' => '2%',
                '0.97' => '3%',
                '0.96' => '4%',
				'0.95' => '5%',
				'0.94' => '6%',
				'0.93' => '7%',
				'0.92' => '8%',
				'0.91' => '9%',
                '0.90' => '10%'
            ],
            'Description' => 'Select percentage to tolerate underpayment when a customer send less crypto than the amount to send.',
            'Default' => '1',
        ),
        'wallet_address' => array(
            'FriendlyName' => 'USD Coin (Bridged) avax-c Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your avax-c-usdc.e Wallet address.',
        ),
    );
}

function usdceavaxc_link($params)
{
    $walletAddress = $params['wallet_address'];
    $paygatedotto_usdceavaxc_underpaidTolerance = $params['underpaid_tolerance'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/usdceavaxc.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_usdceavaxc_currency = $params['currency'];
	$secret = hash('sha256', 'paygate_salt_' . $walletAddress);
	$sig = hash_hmac('sha256', $invoiceId, $secret);
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId . '&sig=' . $sig;

		
$paygatedotto_usdceavaxc_response = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc.e/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_usdceavaxc_currency));


$paygatedotto_usdceavaxc_conversion_resp = json_decode($paygatedotto_usdceavaxc_response, true);

if ($paygatedotto_usdceavaxc_conversion_resp && isset($paygatedotto_usdceavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_usdceavaxc_final_total = $paygatedotto_usdceavaxc_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_usdceavaxc_blockchain_response = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc.e/fees.php');


$paygatedotto_usdceavaxc_blockchain_conversion_resp = json_decode($paygatedotto_usdceavaxc_blockchain_response, true);

if ($paygatedotto_usdceavaxc_blockchain_conversion_resp && isset($paygatedotto_usdceavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_usdceavaxc_response = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc.e/convert.php?value=' . $paygatedotto_usdceavaxc_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_usdceavaxc_conversion_resp = json_decode($paygatedotto_feerevert_usdceavaxc_response, true);

if ($paygatedotto_feerevert_usdceavaxc_conversion_resp && isset($paygatedotto_feerevert_usdceavaxc_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_usdceavaxc_final_total = $paygatedotto_feerevert_usdceavaxc_conversion_resp['value_coin']; 
// output
    $paygatedotto_usdceavaxc_blockchain_final_total = $paygatedotto_feerevert_usdceavaxc_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_usdceavaxc_amount_to_send = $paygatedotto_usdceavaxc_final_total + $paygatedotto_usdceavaxc_blockchain_final_total;		
	
		} else {
	$paygatedotto_usdceavaxc_amount_to_send = $paygatedotto_usdceavaxc_final_total;		
		}
		
		
		
$paygatedottocryptogateway_usdceavaxc_response_minimum = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc.e/info.php');
$paygatedottocryptogateway_usdceavaxc_conversion_resp_minimum = json_decode($paygatedottocryptogateway_usdceavaxc_response_minimum, true);
if ($paygatedottocryptogateway_usdceavaxc_conversion_resp_minimum && isset($paygatedottocryptogateway_usdceavaxc_conversion_resp_minimum['minimum'])) {
    $paygatedottocryptogateway_usdceavaxc_final_total_minimum = $paygatedottocryptogateway_usdceavaxc_conversion_resp_minimum['minimum'];
    if ($paygatedotto_usdceavaxc_amount_to_send < $paygatedottocryptogateway_usdceavaxc_final_total_minimum) {
        return "Error: Payment could not be processed, order total crypto amount to send is less than the minimum allowed for the selected coin";
    }
} else {
    return "Error: Payment could not be processed, can't fetch crypto minimum allowed amount";
}
$paygatedotto_usdceavaxc_gen_wallet = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc.e/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_usdceavaxc_wallet_decbody = json_decode($paygatedotto_usdceavaxc_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_usdceavaxc_wallet_decbody && isset($paygatedotto_usdceavaxc_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_usdceavaxc_gen_addressIn = $paygatedotto_usdceavaxc_wallet_decbody['address_in'];
		$paygatedotto_usdceavaxc_gen_callback = $paygatedotto_usdceavaxc_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_usdceavaxc_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_usdceavaxc_amount_to_send,
'und_tol' => $paygatedotto_usdceavaxc_underpaidTolerance,
'coin_to_send' => 'avax-c_usdc.e'
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
	
	
        $paygatedotto_usdceavaxc_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/avax-c/usdc.e/qrcode.php?address=' . $paygatedotto_usdceavaxc_gen_addressIn);


	$paygatedotto_usdceavaxc_qrcode_decbody = json_decode($paygatedotto_usdceavaxc_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_usdceavaxc_qrcode_decbody && isset($paygatedotto_usdceavaxc_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_usdceavaxc_gen_qrcode = $paygatedotto_usdceavaxc_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_usdceavaxc_gen_qrcode . '" alt="' . $paygatedotto_usdceavaxc_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_usdceavaxc_amount_to_send . '</b> avax-c/usdc.e to the address: <br><b>' . $paygatedotto_usdceavaxc_gen_addressIn . '</b></div>';
}

function usdceavaxc_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'usdceavaxc gateway activated successfully.');
}

function usdceavaxc_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'usdceavaxc gateway deactivated successfully.');
}

function usdceavaxc_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function usdceavaxc_output($vars)
{
    // Output additional information if needed
}

function usdceavaxc_error($vars)
{
    // Handle errors if needed
}
