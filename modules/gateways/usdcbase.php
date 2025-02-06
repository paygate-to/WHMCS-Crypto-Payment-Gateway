<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function usdcbase_MetaData()
{
    return array(
        'DisplayName' => 'usdcbase',
        'DisableLocalCreditCardInput' => true,
    );
}

function usdcbase_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'USD Coin base',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto USD Coin base.',
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
            'FriendlyName' => 'USD Coin base Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your base-usdc Wallet address.',
        ),
    );
}

function usdcbase_link($params)
{
    $walletAddress = $params['wallet_address'];
    $paygatedotto_usdcbase_underpaidTolerance = $params['underpaid_tolerance'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/usdcbase.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_usdcbase_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_usdcbase_response = file_get_contents('https://api.paygate.to/crypto/base/usdc/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_usdcbase_currency));


$paygatedotto_usdcbase_conversion_resp = json_decode($paygatedotto_usdcbase_response, true);

if ($paygatedotto_usdcbase_conversion_resp && isset($paygatedotto_usdcbase_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_usdcbase_final_total = $paygatedotto_usdcbase_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_usdcbase_blockchain_response = file_get_contents('https://api.paygate.to/crypto/base/usdc/fees.php');


$paygatedotto_usdcbase_blockchain_conversion_resp = json_decode($paygatedotto_usdcbase_blockchain_response, true);

if ($paygatedotto_usdcbase_blockchain_conversion_resp && isset($paygatedotto_usdcbase_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_usdcbase_response = file_get_contents('https://api.paygate.to/crypto/base/usdc/convert.php?value=' . $paygatedotto_usdcbase_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_usdcbase_conversion_resp = json_decode($paygatedotto_feerevert_usdcbase_response, true);

if ($paygatedotto_feerevert_usdcbase_conversion_resp && isset($paygatedotto_feerevert_usdcbase_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_usdcbase_final_total = $paygatedotto_feerevert_usdcbase_conversion_resp['value_coin']; 
// output
    $paygatedotto_usdcbase_blockchain_final_total = $paygatedotto_feerevert_usdcbase_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_usdcbase_amount_to_send = $paygatedotto_usdcbase_final_total + $paygatedotto_usdcbase_blockchain_final_total;		
	
		} else {
	$paygatedotto_usdcbase_amount_to_send = $paygatedotto_usdcbase_final_total;		
		}
		
		
		
$paygatedottocryptogateway_usdcbase_response_minimum = file_get_contents('https://api.paygate.to/crypto/base/usdc/info.php');
$paygatedottocryptogateway_usdcbase_conversion_resp_minimum = json_decode($paygatedottocryptogateway_usdcbase_response_minimum, true);
if ($paygatedottocryptogateway_usdcbase_conversion_resp_minimum && isset($paygatedottocryptogateway_usdcbase_conversion_resp_minimum['minimum'])) {
    $paygatedottocryptogateway_usdcbase_final_total_minimum = $paygatedottocryptogateway_usdcbase_conversion_resp_minimum['minimum'];
    if ($paygatedotto_usdcbase_amount_to_send < $paygatedottocryptogateway_usdcbase_final_total_minimum) {
        return "Error: Payment could not be processed, order total crypto amount to send is less than the minimum allowed for the selected coin";
    }
} else {
    return "Error: Payment could not be processed, can't fetch crypto minimum allowed amount";
}
$paygatedotto_usdcbase_gen_wallet = file_get_contents('https://api.paygate.to/crypto/base/usdc/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_usdcbase_wallet_decbody = json_decode($paygatedotto_usdcbase_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_usdcbase_wallet_decbody && isset($paygatedotto_usdcbase_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_usdcbase_gen_addressIn = $paygatedotto_usdcbase_wallet_decbody['address_in'];
		$paygatedotto_usdcbase_gen_callback = $paygatedotto_usdcbase_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_usdcbase_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_usdcbase_amount_to_send,
'und_tol' => $paygatedotto_usdcbase_underpaidTolerance,
'coin_to_send' => 'base_usdc'
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
	
	
        $paygatedotto_usdcbase_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/base/usdc/qrcode.php?address=' . $paygatedotto_usdcbase_gen_addressIn);


	$paygatedotto_usdcbase_qrcode_decbody = json_decode($paygatedotto_usdcbase_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_usdcbase_qrcode_decbody && isset($paygatedotto_usdcbase_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_usdcbase_gen_qrcode = $paygatedotto_usdcbase_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_usdcbase_gen_qrcode . '" alt="' . $paygatedotto_usdcbase_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_usdcbase_amount_to_send . '</b> base/usdc to the address: <br><b>' . $paygatedotto_usdcbase_gen_addressIn . '</b></div>';
}

function usdcbase_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'usdcbase gateway activated successfully.');
}

function usdcbase_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'usdcbase gateway deactivated successfully.');
}

function usdcbase_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function usdcbase_output($vars)
{
    // Output additional information if needed
}

function usdcbase_error($vars)
{
    // Handle errors if needed
}
