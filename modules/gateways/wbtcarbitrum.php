<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function wbtcarbitrum_MetaData()
{
    return array(
        'DisplayName' => 'wbtcarbitrum',
        'DisableLocalCreditCardInput' => true,
    );
}

function wbtcarbitrum_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Wrapped BTC arbitrum',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using crypto Wrapped BTC arbitrum.',
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
            'FriendlyName' => 'Wrapped BTC arbitrum Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your arbitrum-wbtc Wallet address.',
        ),
    );
}

function wbtcarbitrum_link($params)
{
    $walletAddress = $params['wallet_address'];
    $paygatedotto_wbtcarbitrum_underpaidTolerance = $params['underpaid_tolerance'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/wbtcarbitrum.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_wbtcarbitrum_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

		
$paygatedotto_wbtcarbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/wbtc/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_wbtcarbitrum_currency));


$paygatedotto_wbtcarbitrum_conversion_resp = json_decode($paygatedotto_wbtcarbitrum_response, true);

if ($paygatedotto_wbtcarbitrum_conversion_resp && isset($paygatedotto_wbtcarbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_wbtcarbitrum_final_total = $paygatedotto_wbtcarbitrum_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		
		if ($params['blockchain_fees'] === 'on') {
			
	$paygatedotto_wbtcarbitrum_blockchain_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/wbtc/fees.php');


$paygatedotto_wbtcarbitrum_blockchain_conversion_resp = json_decode($paygatedotto_wbtcarbitrum_blockchain_response, true);

if ($paygatedotto_wbtcarbitrum_blockchain_conversion_resp && isset($paygatedotto_wbtcarbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'])) {
    
	// revert blockchain fees back to ticker price
$paygatedotto_feerevert_wbtcarbitrum_response = file_get_contents('https://api.paygate.to/crypto/arbitrum/wbtc/convert.php?value=' . $paygatedotto_wbtcarbitrum_blockchain_conversion_resp['estimated_cost_currency']['USD'] . '&from=usd');


$paygatedotto_feerevert_wbtcarbitrum_conversion_resp = json_decode($paygatedotto_feerevert_wbtcarbitrum_response, true);

if ($paygatedotto_feerevert_wbtcarbitrum_conversion_resp && isset($paygatedotto_feerevert_wbtcarbitrum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_feerevert_wbtcarbitrum_final_total = $paygatedotto_feerevert_wbtcarbitrum_conversion_resp['value_coin']; 
// output
    $paygatedotto_wbtcarbitrum_blockchain_final_total = $paygatedotto_feerevert_wbtcarbitrum_final_total; 	
} else {
	return "Error: Payment could not be processed, please try again (unable to get estimated cost)";
}
     
} else {
	return "Error: Payment could not be processed, estimated blockchain cost unavailable";
}	

    $paygatedotto_wbtcarbitrum_amount_to_send = $paygatedotto_wbtcarbitrum_final_total + $paygatedotto_wbtcarbitrum_blockchain_final_total;		
	
		} else {
	$paygatedotto_wbtcarbitrum_amount_to_send = $paygatedotto_wbtcarbitrum_final_total;		
		}
		
		
		
$paygatedottocryptogateway_wbtcarbitrum_response_minimum = file_get_contents('https://api.paygate.to/crypto/arbitrum/wbtc/info.php');
$paygatedottocryptogateway_wbtcarbitrum_conversion_resp_minimum = json_decode($paygatedottocryptogateway_wbtcarbitrum_response_minimum, true);
if ($paygatedottocryptogateway_wbtcarbitrum_conversion_resp_minimum && isset($paygatedottocryptogateway_wbtcarbitrum_conversion_resp_minimum['minimum'])) {
    $paygatedottocryptogateway_wbtcarbitrum_final_total_minimum = $paygatedottocryptogateway_wbtcarbitrum_conversion_resp_minimum['minimum'];
    if ($paygatedotto_wbtcarbitrum_amount_to_send < $paygatedottocryptogateway_wbtcarbitrum_final_total_minimum) {
        return "Error: Payment could not be processed, order total crypto amount to send is less than the minimum allowed for the selected coin";
    }
} else {
    return "Error: Payment could not be processed, can't fetch crypto minimum allowed amount";
}
$paygatedotto_wbtcarbitrum_gen_wallet = file_get_contents('https://api.paygate.to/crypto/arbitrum/wbtc/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_wbtcarbitrum_wallet_decbody = json_decode($paygatedotto_wbtcarbitrum_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_wbtcarbitrum_wallet_decbody && isset($paygatedotto_wbtcarbitrum_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_wbtcarbitrum_gen_addressIn = $paygatedotto_wbtcarbitrum_wallet_decbody['address_in'];
		$paygatedotto_wbtcarbitrum_gen_callback = $paygatedotto_wbtcarbitrum_wallet_decbody['callback_url'];
		
		$paygatedotto_jsonObject = json_encode(array(
'pay_to_address' => $paygatedotto_wbtcarbitrum_gen_addressIn,
'crypto_amount_to_send' => $paygatedotto_wbtcarbitrum_amount_to_send,
'und_tol' => $paygatedotto_wbtcarbitrum_underpaidTolerance,
'coin_to_send' => 'arbitrum_wbtc'
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
	
	
        $paygatedotto_wbtcarbitrum_gen_qrcode = file_get_contents('https://api.paygate.to/crypto/arbitrum/wbtc/qrcode.php?address=' . $paygatedotto_wbtcarbitrum_gen_addressIn);


	$paygatedotto_wbtcarbitrum_qrcode_decbody = json_decode($paygatedotto_wbtcarbitrum_gen_qrcode, true);

 // Check if decoding was successful
    if ($paygatedotto_wbtcarbitrum_qrcode_decbody && isset($paygatedotto_wbtcarbitrum_qrcode_decbody['qr_code'])) {
        // Store the qr_code as a variable
        $paygatedotto_wbtcarbitrum_gen_qrcode = $paygatedotto_wbtcarbitrum_qrcode_decbody['qr_code'];		
    } else {
return "Error: QR code could not be processed, please try again (wallet address error)";
    }

        // Properly encode attributes for HTML output
        return '<div><img src="data:image/png;base64,' . $paygatedotto_wbtcarbitrum_gen_qrcode . '" alt="' . $paygatedotto_wbtcarbitrum_gen_addressIn . '"></div><div>Please send <b>' . $paygatedotto_wbtcarbitrum_amount_to_send . '</b> arbitrum/wbtc to the address: <br><b>' . $paygatedotto_wbtcarbitrum_gen_addressIn . '</b></div>';
}

function wbtcarbitrum_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'wbtcarbitrum gateway activated successfully.');
}

function wbtcarbitrum_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'wbtcarbitrum gateway deactivated successfully.');
}

function wbtcarbitrum_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function wbtcarbitrum_output($vars)
{
    // Output additional information if needed
}

function wbtcarbitrum_error($vars)
{
    // Handle errors if needed
}
