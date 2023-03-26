<?php
/**
 * Game Control Panel v2
 * Copyright (c) www.intrepid-web.net
 *
 * The use of this product is subject to a license agreement
 * which can be found at http://www.intrepid-web.net/rf-game-cp-v2/license-agreement/
 */
define('ODP_RECHECK_7Z_ENCRYPT75469009373', true);
// Include Main Files
include('./gamecp_common.php');

/**
 * Copyright (C) 2007 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This is the response handler code that will be invoked every time
 * a notification or request is sent by the Google Server
 *
 * To allow this code to receive responses, the url for this file
 * must be set on the seller page under Settings->Integration as the
 * "API Callback URL'
 * Order processing commands can be sent automatically by placing these
 * commands appropriately
 *
 * To use this code for merchant-calculated feedback, this url must be
 * set also as the merchant-calculations-url when the cart is posted
 * Depending on your calculations for shipping, taxes, coupons and gift
 * certificates update parts of the code as required
 */
require_once('./includes/google_library/googleresponse.php');
require_once('./includes/google_library/googlemerchantcalculations.php');
require_once('./includes/google_library/googleresult.php');
require_once('./includes/google_library/googlerequest.php');

define('RESPONSE_HANDLER_ERROR_LOG_FILE', 'googleerror.log');
define('RESPONSE_HANDLER_LOG_FILE', 'googlemessage.log');

if (!isset($config['google_merchant_id']) && !isset($config['google_merchant_key']) && !isset($config['google_server_type']) && !isset($config['google_currency'])) {
    die("Missing configuration info");
}

$merchant_id = $config['google_merchant_id']; // Your Merchant ID
$merchant_key = $config['google_merchant_key']; // Your Merchant Key
$server_type = $config['google_server_type'];
$currency = $config['google_currency'];

$Gresponse = new GoogleResponse($merchant_id, $merchant_key);
$Grequest = new GoogleRequest($merchant_id, $merchant_key, $server_type, $currency);
// Setup the log file
$Gresponse->SetLogFiles(RESPONSE_HANDLER_ERROR_LOG_FILE, RESPONSE_HANDLER_LOG_FILE, L_ALL);
// Retrieve the XML sent in the HTTP POST request to the ResponseHandler
$xml_response = isset($HTTP_RAW_POST_DATA) ?
    $HTTP_RAW_POST_DATA : file_get_contents("php://input");
if (get_magic_quotes_gpc()) {
    $xml_response = stripslashes($xml_response);
}
list($root, $data) = $Gresponse->GetParsedXML($xml_response);
$Gresponse->SetMerchantAuthentication($merchant_id, $merchant_key);

$status = $Gresponse->HttpAuthentication();
if (!$status) {
    gamecp_log(5, 'Google', "GOOGLE - Authentication Failure");
    die('authentication failed');
}

/* Commands to send the various order processing APIs
	 * Send charge order : $Grequest->SendChargeOrder($data[$root]
	 *    ['google-order-number']['VALUE'], <amount>);
	 * Send process order : $Grequest->SendProcessOrder($data[$root]
	 *    ['google-order-number']['VALUE']);
	 * Send deliver order: $Grequest->SendDeliverOrder($data[$root]
	 *    ['google-order-number']['VALUE'], <carrier>, <tracking-number>,
	 *    <send_mail>);
	 * Send archive order: $Grequest->SendArchiveOrder($data[$root]
	 *    ['google-order-number']['VALUE']);
	 *
	 */

switch ($root) {
    case "request-received":
    {
        break;
    }
    case "error":
    {
        break;
    }
    case "diagnosis":
    {
        break;
    }
    case "checkout-redirect":
    {
        break;
    }
    case "merchant-calculation-callback":
    {
        // Create the results and send it
        $merchant_calc = new GoogleMerchantCalculations($currency);
        // Loop through the list of address ids from the callback
        $addresses = get_arr_result($data[$root]['calculate']['addresses']['anonymous-address']);
        foreach ($addresses as $curr_address) {
            $curr_id = $curr_address['id'];
            $country = $curr_address['country-code']['VALUE'];
            $city = $curr_address['city']['VALUE'];
            $region = $curr_address['region']['VALUE'];
            $postal_code = $curr_address['postal-code']['VALUE'];
            // Loop through each shipping method if merchant-calculated shipping
            // support is to be provided
            if (isset($data[$root]['calculate']['shipping'])) {
                $shipping = get_arr_result($data[$root]['calculate']['shipping']['method']);
                foreach ($shipping as $curr_ship) {
                    $name = $curr_ship['name'];
                    // Compute the price for this shipping method and address id
                    $price = 12; // Modify this to get the actual price
                    $shippable = "true"; // Modify this as required
                    $merchant_result = new GoogleResult($curr_id);
                    $merchant_result->SetShippingDetails($name, $price, $shippable);

                    if ($data[$root]['calculate']['tax']['VALUE'] == "true") {
                        // Compute tax for this address id and shipping type
                        $amount = 15; // Modify this to the actual tax value
                        $merchant_result->SetTaxDetails($amount);
                    }

                    if (isset($data[$root]['calculate']['merchant-code-strings']['merchant-code-string'])) {
                        $codes = get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
                        foreach ($codes as $curr_code) {
                            // Update this data as required to set whether the coupon is valid, the code and the amount
                            $coupons = new GoogleCoupons("true", $curr_code['code'], 5, "test2");
                            $merchant_result->AddCoupons($coupons);
                        }
                    }
                    $merchant_calc->AddResult($merchant_result);
                }
            } else {
                $merchant_result = new GoogleResult($curr_id);
                if ($data[$root]['calculate']['tax']['VALUE'] == "true") {
                    // Compute tax for this address id and shipping type
                    $amount = 15; // Modify this to the actual tax value
                    $merchant_result->SetTaxDetails($amount);
                }
                $codes = get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
                foreach ($codes as $curr_code) {
                    // Update this data as required to set whether the coupon is valid, the code and the amount
                    $coupons = new GoogleCoupons("true", $curr_code['code'], 5, "test2");
                    $merchant_result->AddCoupons($coupons);
                }
                $merchant_calc->AddResult($merchant_result);
            }
        }
        $Gresponse->ProcessMerchantCalculations($merchant_calc);
        break;
    }
    case "new-order-notification":
    {

        $credits = @calculate_credits($config['donations_credit_muntiplier'], $config['donations_number_of_pay_options'], $config['donations_start_price'], $config['donations_start_credits'], round($data[$root]['order-total']['VALUE'], 2));

        $orderNumber = $data[$root]['google-order-number']['VALUE'];
        $buyerId = $data[$root]['buyer-id']['VALUE'];
        $userId = $data[$root]['shopping-cart']['items']['item']['merchant-private-item-data']['VALUE'];
        $priceTotal = number_format($data[$root]['order-total']['VALUE'], 2, '.', '');
        $userPoints = $credits;
        $buyerEmail = $data[$root]['buyer-billing-address']['email']['VALUE'];
        $buyerName = $data[$root]['buyer-billing-address']['contact-name']['VALUE'];
        $buyerAddress = $data[$root]['buyer-billing-address']['address1']['VALUE'];
        $postalCode = $data[$root]['buyer-billing-address']['postal-code']['VALUE'];

        # get user name!
        connectuserdb();
        $user_info_sql = "SELECT convert(varchar,id) AS AccountName FROM tbl_UserAccount WHERE Serial = '" . $userId . "'";
        if (!($user_info_result = mssql_query($user_info_sql))) {
            // Always log this in the admin logs!
            gamecp_log(0, $custom, "GOOGLE - ERROR - Unable to find or query this user id");
        }
        $user = mssql_fetch_array($user_info_result);

        if ($user['AccountName'] != '') {
            $user_name = antiject($user['AccountName']);
        } else {
            $user_name = $userId;
        }

        # Connect to our game cp db
        connectgamecpdb();

        // Query for duplicate TXN_IDS
        $orderid_query = mssql_query('SELECT google_order_id FROM gamecp_google_payments WHERE google_order_id="' . $orderId . '"');

        // Now the check to see if we got results, if not-- success!
        if (mssql_num_rows($orderid_query) <= 0) {

            # Okay, lets insert our data in our google table
            $google_query = "INSERT INTO gamecp_google_payments (google_order_id, google_order_price, google_order_points, google_buyer_id, google_buyer_email, google_account_serial, google_buyer_name, google_buyer_address, google_buyer_postal_code, google_order_state, google_time) VALUES ('" . $orderNumber . "', '" . $priceTotal . "', '" . $userPoints . "', '" . $buyerId . "', '" . antiject($buyerEmail) . "', '" . antiject($userId) . "', '" . antiject($buyerName) . "', '" . antiject($buyerAddress) . "', '" . antiject($postalCode) . "', '1', '" . time() . "')";
            mssql_query($google_query);

            gamecp_log(0, $user_name, "GOOGLE - NEW ORDER - ID: $orderNumber | E-Mail: $buyerEmail | Price: $" . $priceTotal, 1);

        } else {

            gamecp_log(0, $user_name, "GOOGLE - DUPLICATE ORDER - ID: $orderNumber | E-Mail: $buyerEmail | Price: $" . $priceTotal, 1);

        }

        $Gresponse->SendAck();
        break;
    }
    case "authorization-amount-notification":
    {
        $fp = fopen('test.txt', 'a');
        fwrite($fp, 'authorization-amount-notification' . "\n");
        fclose($fp);
        break;
    }
    case "order-state-change-notification":
    {
        $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
        $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];

        # First obtain order number
        $orderId = $data[$root]['google-order-number']['VALUE'];

        # Connect to Game CP
        connectgamecpdb();

        $select_order = mssql_query("SELECT google_order_price, google_order_points, google_order_id, google_account_serial, google_buyer_email FROM gamecp_google_payments WHERE google_order_id = '$orderId'");

        if (mssql_num_rows($select_order) >= 1) {

            # Fetch data
            $google = @mssql_fetch_array($select_order);

            # Get some info we may need to display
            $Email = $google['google_buyer_email'];

            # Get user name!
            connectuserdb();
            $user_info_sql = "SELECT convert(varchar,id) AS AccountName FROM tbl_UserAccount WHERE Serial = '" . $google['google_account_serial'] . "'";
            if (!($user_info_result = mssql_query($user_info_sql))) {
                // Always log this in the admin logs!
                gamecp_log(0, $custom, "GOOGLE - ERROR - Unable to find or query this user id [CHARGE]");
            }
            $user = mssql_fetch_array($user_info_result);

            if ($user['AccountName'] != '') {
                $user_name = antiject($user['AccountName']);
            } else {
                $user_name = $google['google_account_serial'];
            }

            switch ($new_financial_state) {
                case 'REVIEWING':
                {
                    break;
                }
                case 'CHARGEABLE':
                {
                    // $Grequest->SendProcessOrder($data[$root]['google-order-number']['VALUE']);
                    // $Grequest->SendChargeOrder($data[$root]['google-order-number']['VALUE'],'');
                    break;
                }
                case 'CHARGING':
                {
                    break;
                }
                case 'CHARGED':
                {
                    break;
                }
                case 'PAYMENT_DECLINED':
                {
                    gamecp_log(3, $user_name, "GOOGLE - PAYMENT DECLINED - ID: $orderId | EMail: $Email");
                    break;
                }
                case 'CANCELLED':
                {
                    gamecp_log(3, $user_name, "GOOGLE - CANCELLED - ID: $orderId | EMail: $Email");
                    break;
                }
                case 'CANCELLED_BY_GOOGLE':
                {
                    $Grequest->SendBuyerMessage($data[$root]['google-order-number']['VALUE'],
                        "Sorry, your order is cancelled by Google", true);
                    gamecp_log(3, $user_name, "GOOGLE - CANCELLED BY GOOGLE - ID: $orderId");
                    break;
                }

                default:
                    break;
            }

            switch ($new_fulfillment_order) {
                case 'NEW':
                {
                    break;
                }
                case 'PROCESSING':
                {
                    break;
                }
                case 'DELIVERED':
                {
                    # Write Log
                    gamecp_log(0, $user_name, "GOOGLE - SUCCESSFUL ORDER - ID: $orderId", 1);

                    # Connect to Game CP
                    connectgamecpdb();

                    # Now lets update the state of this order
                    $google_query = "UPDATE gamecp_google_payments SET google_order_state = '3' WHERE google_order_id = '$orderId'";
                    mssql_query($google_query);

                    # Before we move on, lets get the points shall we?
                    $userPoints = @calculate_credits($config['donations_credit_muntiplier'], $config['donations_number_of_pay_options'], $config['donations_start_price'], $config['donations_start_credits'], round($google['google_order_price'], 2));

                    # Moving on to do the credit inserts
                    // Okay, now we need to check to see if we do a 'insert' or an 'update'
                    $totalusers_query = @mssql_query('SELECT user_points FROM gamecp_gamepoints WHERE user_account_id="' . trim($google['google_account_serial']) . '"');

                    // 0 means insert, else update
                    if (mssql_num_rows($totalusers_query) == 0) {
                        // Create a new row, add credits
                        $points_in = 'INSERT INTO gamecp_gamepoints (user_account_id, user_points) VALUES ("' . $google['google_account_serial'] . '", "' . $userPoints . '")';
                        mssql_query($points_in);

                        // Always log this in the admin logs!
                        gamecp_log(0, $user_name, "GOOGLE - ADDED POINTS - INSERT - ID: $orderId | Points: $userPoints");
                    } else {
                        // Update the current row with the new credits :D
                        $points_in = 'UPDATE gamecp_gamepoints SET user_points=user_points+' . $userPoints . ' WHERE user_account_id="' . $google['google_account_serial'] . '"';
                        mssql_query($points_in);

                        // Always log this in the admin logs!
                        gamecp_log(0, $user_name, "GOOGLE - ADDED POINTS - UPDATE - ID: $orderId | Points: $userPoints");
                    }
                    break;
                }
                case 'WILL_NOT_DELIVER':
                {
                    gamecp_log(4, $user_name, "GOOGLE - NOT DELIVERED - ID: $orderId | EMail: $Email | Might be an error in your setup!");
                    break;
                }

                default:
                    break;
            }
            break;


        } else {

            # Write Log
            connectgamecpdb();
            gamecp_log(4, 'Google', "GOOGLE - ORDER ERROR - ID: $orderId | Cannot find order in database!");

        }

        $Gresponse->SendAck();
    }
    case "charge-amount-notification":
    {
        // $Grequest->SendDeliverOrder($data[$root]['google-order-number']['VALUE'],
        // <carrier>, <tracking-number>, <send-email>);
        // $Grequest->SendArchiveOrder($data[$root]['google-order-number']['VALUE'] );
        $Gresponse->SendAck();
        break;
    }
    case "chargeback-amount-notification":
    {
        # First obtain order number
        $orderId = $data[$root]['google-order-number']['VALUE'];
        $amount = $data[$root]['total-chargeback-amount']['VALUE'];

        # Connect to Game CP
        connectgamecpdb();

        $select_order = mssql_query("SELECT google_order_price, google_order_points, google_order_id, google_account_serial, google_buyer_email FROM gamecp_google_payments WHERE google_order_id = '$orderId'");

        if (mssql_num_rows($select_order) >= 1) {

            # Fetch data
            $google = @mssql_fetch_array($select_order);

            # Get user name!
            connectuserdb();
            $user_info_sql = "SELECT convert(varchar,id) AS AccountName FROM tbl_UserAccount WHERE Serial = '" . $google['google_account_serial'] . "'";
            if (!($user_info_result = mssql_query($user_info_sql))) {
                // Always log this in the admin logs!
                gamecp_log(0, $custom, "GOOGLE - ERROR - Unable to find or query this user id [CHARGE]");
            }
            $user = mssql_fetch_array($user_info_result);

            if ($user['AccountName'] != '') {
                $user_name = antiject($user['AccountName']);
            } else {
                $user_name = $google['google_account_serial'];
            }

            $EMail = $google['google_buyer_email'];

            gamecp_log(5, $user_name, "GOOGLE - CHARGEBACK - ID: $orderId | EMail: $EMail | Amount: $amount");

        } else {

            # Write Log
            connectgamecpdb();
            gamecp_log(5, 'Google', "GOOGLE - ORDER CHARGEBACK ERROR - ID: $orderId | Cannot find order in database!");

        }

        $Gresponse->SendAck();
        break;
    }
    case "refund-amount-notification":
    {
        # First obtain order number
        $orderId = $data[$root]['google-order-number']['VALUE'];
        $amount = $data[$root]['total-chargeback-amount']['VALUE'];

        # Connect to Game CP
        connectgamecpdb();

        $select_order = mssql_query("SELECT google_order_price, google_order_points, google_order_id, google_account_serial, google_buyer_email FROM gamecp_google_payments WHERE google_order_id = '$orderId'");

        if (mssql_num_rows($select_order) >= 1) {

            # Fetch data
            $google = @mssql_fetch_array($select_order);

            # Get user name!
            connectuserdb();
            $user_info_sql = "SELECT convert(varchar,id) AS AccountName FROM tbl_UserAccount WHERE Serial = '" . $google['google_account_serial'] . "'";
            if (!($user_info_result = mssql_query($user_info_sql))) {
                // Always log this in the admin logs!
                gamecp_log(0, $custom, "GOOGLE - ERROR - Unable to find or query this user id [CHARGE]");
            }
            $user = mssql_fetch_array($user_info_result);

            if ($user['AccountName'] != '') {
                $user_name = antiject($user['AccountName']);
            } else {
                $user_name = $google['google_account_serial'];
            }

            $EMail = $google['google_buyer_email'];

            gamecp_log(5, $user_name, "GOOGLE - REFUND - ID: $orderId | EMail: $EMail | Amount: $amount");

        } else {

            # Write Log
            connectgamecpdb();
            gamecp_log(5, 'Google', "GOOGLE - ORDER REFUND ERROR - ID: $orderId | Cannot find order in database!");

        }

        $Gresponse->SendAck();
        break;
    }
    case "risk-information-notification":
    {
        # First obtain order number
        $orderId = $data[$root]['google-order-number']['VALUE'];
        $buyerIP = $data[$root]['risk-information']['ip-address']['VALUE'];
        $buyerAge = $data[$root]['risk-information']['buyer-account-age']['VALUE'];

        # Now lets update the state of this order
        $google_query = "UPDATE gamecp_google_payments SET google_buyer_ip = '$buyerIP', google_buyer_account_age = '$buyerAge' WHERE google_order_id = '$orderId'";
        mssql_query($google_query);

        $Gresponse->SendAck();
        break;
    }

    default:
        $Gresponse->SendBadRequestStatus("Invalid or not supported Message");
        break;
}

/* In case the XML API contains multiple open tags
     with the same value, then invoke this function and
     perform a foreach on the resultant array.
     This takes care of cases when there is only one unique tag
     or multiple tags.
     Examples of this are "anonymous-address", "merchant-code-string"
     from the merchant-calculations-callback API
  */
function get_arr_result($child_node)
{
    $result = array();
    if (isset($child_node)) {
        if (is_associative_array($child_node)) {
            $result[] = $child_node;
        } else {
            foreach ($child_node as $curr_node) {
                $result[] = $curr_node;
            }
        }
    }
    return $result;
}

/* Returns true if a given variable represents an associative array */
function is_associative_array($var)
{
    return is_array($var) && !is_numeric(implode('', array_keys($var)));
}

?>