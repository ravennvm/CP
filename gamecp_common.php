<?php
/**
 * Game Control Panel v2
 * Copyright (c) www.intrepid-web.net
 *
 * The use of this product is subject to a license agreement
 * which can be found at http://www.intrepid-web.net/rf-game-cp-v2/license-agreement/
 * 
 * 
 */
if (!defined("ODP_RECHECK_7Z_ENCRYPT75469009373")) {
    die("Hacking Attempt");
    exit;
    return;
}

# Set content header
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL);
ini_set("display_errors", 1);

# Define common initaited
define("COMMON_INITIATED", true);

# Required globally
$base_path = dirname(__FILE__);

# Fast, set this up now!
function quick_msg($message, $type = 'error')
{
    ?>
    <head>
        <title>RESPONE FROM SERVER</title>
        <style type="text/css">
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 13px;
                background-color: #F7F7F7;
                margin: 50px;
                padding: 20px;
            }

            a {
                color: #580000;
                text-decoration: none;
            }

            a:hover {
                color: #C76E0F;
            }

            .info, .success, .warning, .error, .validation {
                border: 1px solid;
                margin: 10px 0px;
                padding: 15px 10px 15px 50px;
                background-repeat: no-repeat;
                background-position: 10px center;
            }

            .info {
                color: #00529B;
                background-color: #BDE5F8;
                background-image: url('./includes/images/knobs/info.png');
            }

            .success {
                color: #4F8A10;
                background-color: #DFF2BF;
                background-image: url('./includes/images/knobs/success.png');
            }

            .warning {
                color: #9F6000;
                background-color: #FEEFB3;
                background-image: url('./includes/images/knobs/warning.png');
            }

            .error {
                color: #D8000C;
                background-color: #FFBABA;
                background-image: url('./includes/images/knobs/error.png');
            }
        </style>
    </head>

    <body>
    <h2>Hello !</h2>
    <?php
    echo '<div class="' . $type . '">' . $message . '</div>';
    ?>
    <div style="text-align: center;">
        <small>Copyright &copy; 2009 - <a href="#">RLZ</a></small>
    </div>
    </body>
    </html>
    <?php

    exit(1);
}


# Check to see if we have setup our stuff?
if (!file_exists('./includes/main/config.php')) {
    quick_msg("Please setup your Game Control Panel config.php (re-name config.php.edit to config.php)");
}

# Check to see if we our definition file
if (!file_exists('./includes/main/definitions.php')) {
    quick_msg("Please go into your includes/main/ and rename definitions.php.edit to definitions.php. Edit its contents only if needed!", 'warning');
}

# Well, we really cannot do anything is MSSQL is not installed :(
if (@phpversion() >= '5.3.0' && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    if (extension_loaded('sqlsrv')) {
        include "./includes/main/mssql_to_sqlsrv.php";
    } elseif(!extension_loaded('mssql')) {
        quick_msg("Your server, running PHP 5.3+ does not have the SQLSRV module loaded OR the MSSQL module loaded");
    }
} else {
    if (!function_exists('mssql_connect')) {
        quick_msg("Your server does not have the MSSQL module loaded with PHP");
    }
}

# Make sure we can read/write to our cache directory
if (!is_dir('./includes/cache/')) {
    quick_msg("Woops! Please create the cache folder");
}

# Make sure
if (!is_writable('./includes/cache')) {
    quick_msg("Woops! It looks like I cannot read/write to the /includes/cache/ folder. Make sure I have the right permissions");
}

# I'm going to do some variable checking and fixing
# Seems that some web servers don't provide soem key varaibles! REQUEST_URI and DOCUMENT_ROOT? Seriously
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/' . substr($_SERVER['PHP_SELF'], 1);

    if (isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != "") {
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
}

if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}

if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    if (isset($_SERVER['PATH_TRANSLATED'])) {
        $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
    }
}


# Begin session
@session_start();

# Include Main Files
include('./includes/main/functions.php');

include('./includes/main/config.php');
include('./includes/main/definitions.php');

# Major security script taken from phpBB 2.x
if (get_magic_quotes_runtime()) {
    quick_msg('Sorry, cannot have magic quotes enabled. Please disable magic quotes in your php.ini');
}

// The following code (unsetting globals)
// Thanks to Matt Kavanagh and Stefan Esser for providing feedback as well as patch files

// PHP5 with register_long_arrays off?
if (@phpversion() >= '5.0.0' && (!@ini_get('register_long_arrays') || @ini_get('register_long_arrays') == '0' || strtolower(@ini_get('register_long_arrays')) == 'off')) {
    $HTTP_POST_VARS = $_POST;
    $HTTP_GET_VARS = $_GET;
    $HTTP_SERVER_VARS = $_SERVER;
    $HTTP_COOKIE_VARS = $_COOKIE;
    $HTTP_ENV_VARS = $_ENV;
    $HTTP_POST_FILES = $_FILES;

    // _SESSION is the only superglobal which is conditionally set
    if (isset($_SESSION)) {
        $HTTP_SESSION_VARS = $_SESSION;
    }
}

// Protect against GLOBALS tricks
if (isset($HTTP_POST_VARS['GLOBALS']) || isset($HTTP_POST_FILES['GLOBALS']) || isset($HTTP_GET_VARS['GLOBALS']) || isset($HTTP_COOKIE_VARS['GLOBALS'])) {
    die("Hacking attempt");
}

// Protect against HTTP_SESSION_VARS tricks
if (isset($HTTP_SESSION_VARS) && !is_array($HTTP_SESSION_VARS)) {
    die("Hacking attempt");
}

if (@ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on') {
    // PHP4+ path
    $not_unset = array('HTTP_GET_VARS', 'HTTP_POST_VARS', 'HTTP_COOKIE_VARS', 'HTTP_SERVER_VARS', 'HTTP_SESSION_VARS', 'HTTP_ENV_VARS', 'HTTP_POST_FILES', 'phpEx', 'phpbb_root_path');

    // Not only will array_merge give a warning if a parameter
    // is not an array, it will actually fail. So we check if
    // HTTP_SESSION_VARS has been initialised.
    if (!isset($HTTP_SESSION_VARS) || !is_array($HTTP_SESSION_VARS)) {
        $HTTP_SESSION_VARS = array();
    }

    // Merge all into one extremely huge array; unset
    // this later
    $input = array_merge($HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_SESSION_VARS, $HTTP_ENV_VARS, $HTTP_POST_FILES);

    unset($input['input']);
    unset($input['not_unset']);

    while (list($var,) = @each($input)) {
        if (in_array($var, $not_unset)) {
            die('Hacking attempt!');
        }
        unset($$var);
    }

    unset($input);
}

# Do some SQL Injects checks
//
// addslashes to vars if magic_quotes_gpc is off
// this is a security precaution to prevent someone
// trying to break out of a SQL statement.
//
//if( !get_magic_quotes_gpc() )
//{
if (is_array($_GET)) {
    while (list($k, $v) = each($_GET)) {
        if (is_array($_GET[$k])) {
            while (list($k2, $v2) = each($_GET[$k])) {
                $_GET[$k][$k2] = antiject($v2);
            }
            @reset($_GET[$k]);
        } else {
            $_GET[$k] = antiject($v);
        }
    }
    @reset($_GET);
}

if (is_array($_POST)) {
    while (list($k, $v) = each($_POST)) {
        if (is_array($_POST[$k])) {
            while (list($k2, $v2) = each($_POST[$k])) {
                $_POST[$k][$k2] = antiject($v2);
            }
            @reset($_POST[$k]);
        } else {
            $_POST[$k] = antiject($v);
        }
    }
    @reset($_POST);
}

if (is_array($_COOKIE)) {
    while (list($k, $v) = each($_COOKIE)) {
        if (is_array($_COOKIE[$k])) {
            while (list($k2, $v2) = each($_COOKIE[$k])) {
                $_COOKIE[$k][$k2] = antiject($v2);
            }
            @reset($_COOKIE[$k]);
        } else {
            $_COOKIE[$k] = antiject($v);
        }
    }
    @reset($_COOKIE);
}
//}


/*
* Remove variables created by register_globals from the global scope
* Thanks to Matt Kavanagh
*/
function deregister_globals()
{
    $not_unset = array(
        'GLOBALS' => true,
        '_GET' => true,
        '_POST' => true,
        '_COOKIE' => true,
        '_REQUEST' => true,
        '_SERVER' => true,
        '_SESSION' => true,
        '_ENV' => true,
        '_FILES' => true,
        'phpEx' => true,
        'phpbb_root_path' => true
    );

    // Not only will array_merge and array_keys give a warning if
    // a parameter is not an array, array_merge will actually fail.
    // So we check if _SESSION has been initialised.
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        $_SESSION = array();
    }

    // Merge all into one extremely huge array; unset this later
    $input = array_merge(
        array_keys($_GET),
        array_keys($_POST),
        array_keys($_COOKIE),
        array_keys($_SERVER),
        array_keys($_SESSION),
        array_keys($_ENV),
        array_keys($_FILES)
    );

    foreach ($input as $varname) {
        if (isset($not_unset[$varname])) {
            // Hacking attempt. No point in continuing unless it's a COOKIE
            if ($varname !== 'GLOBALS' || isset($_GET['GLOBALS']) || isset($_POST['GLOBALS']) || isset($_SERVER['GLOBALS']) || isset($_SESSION['GLOBALS']) || isset($_ENV['GLOBALS']) || isset($_FILES['GLOBALS'])) {
                exit;
            } else {
                $cookie = & $_COOKIE;
                while (isset($cookie['GLOBALS'])) {
                    foreach ($cookie['GLOBALS'] as $registered_var => $value) {
                        if (!isset($not_unset[$registered_var])) {
                            unset($GLOBALS[$registered_var]);
                        }
                    }
                    $cookie = & $cookie['GLOBALS'];
                }
            }
        }

        unset($GLOBALS[$varname]);
    }

    unset($input);
}

// If we are on PHP >= 6.0.0 we do not need some code
if (version_compare(PHP_VERSION, '6.0.0-dev', '>=')) {
    /**
     * @ignore
     */
    define('STRIP', false);
} else {
    @set_magic_quotes_runtime(0);

    // Be paranoid with passed vars
    if (@ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on' || !function_exists('ini_get')) {
        deregister_globals();
    }

    define('STRIP', (get_magic_quotes_gpc()) ? true : false);
}

// ------------------------------------------------
// Validate an input for a proper ipa ddress
// @param:	string	ip address
// @result: boolean
// @copyright: admin [_at_] webbsense [d=o=t] com http://algorytmy.pl/doc/php/function.getenv.php
// ------------------------------------------------
function validip($ip)
{
    if (!empty($ip) && ip2long($ip) != -1) {
        $reserved_ips = array(
            array('0.0.0.0', '2.255.255.255'),
            array('10.0.0.0', '10.255.255.255'),
            array('127.0.0.0', '127.255.255.255'),
            array('169.254.0.0', '169.254.255.255'),
            array('172.16.0.0', '172.31.255.255'),
            array('192.0.2.0', '192.0.2.255'),
            array('192.168.0.0', '192.168.255.255'),
            array('255.255.255.0', '255.255.255.255')
        );

        foreach ($reserved_ips as $r) {
            $min = ip2long($r[0]);
            $max = ip2long($r[1]);
            if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
        }
        return true;
    } else {
        return false;
    }
}

// ------------------------------------------------
// Get the current users IP Address
// @result:	string	user ip
// @copyright: admin [_at_] webbsense [d=o=t] com http://algorytmy.pl/doc/php/function.getenv.php
// Code has been modified to fix errors
// ------------------------------------------------
function getip()
{
    if (isset($_SERVER["HTTP_CLIENT_IP"]) && validip($_SERVER["HTTP_CLIENT_IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        foreach (explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
            if (validip(trim($ip))) {
                return $ip;
            }
        }
    }
    if (isset($_SERVER["HTTP_X_FORWARDED"]) && validip($_SERVER["HTTP_X_FORWARDED"])) {
        return $_SERVER["HTTP_X_FORWARDED"];
    } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"]) && validip($_SERVER["HTTP_FORWARDED_FOR"])) {
        return $_SERVER["HTTP_FORWARDED_FOR"];
    } elseif (isset($_SERVER["HTTP_FORWARDED"]) && validip($_SERVER["HTTP_FORWARDED"])) {
        return $_SERVER["HTTP_FORWARDED"];
    } elseif (isset($_SERVER["HTTP_X_FORWARDED"]) && validip($_SERVER["HTTP_X_FORWARDED"])) {
        return $_SERVER["HTTP_X_FORWARDED"];
    } else {
        return $_SERVER["REMOTE_ADDR"];
    }
}

$_SERVER["REMOTE_ADDR"] = getip();

# First, lets get our configuration variables, shall we?
connectgamecpdb();
$config_query = "SELECT config_name, config_value FROM gamecp_config";
if (!($config_result = mssql_query($config_query))) {
    echo "Unable to obtain data from the configuration database";
    exit;
}
while ($row = mssql_fetch_array($config_result)) {
    $config[$row['config_name']] = $row['config_value'];
}
mssql_free_result($config_result);
mssql_close($gamecp_dbconnect);
# End Config

# Set error handler
if ($config['security_enable_debug'] == 1) {
    error_reporting(E_ALL ^ E_NOTICE);
} else {
    error_reporting(0);
}
set_error_handler('errorHandler');
# End Erro Handler

# Set some variables here
$script_name = (isset($config['gamecp_filename'])) ? $config['gamecp_filename'] : 'index.php';
$program_name = (isset($config['gamecp_programname'])) ? $config['gamecp_programname'] : 'Game CP';
$super_admin = explode(",", $admin['super_admin']);
$allowed_ips = ($admin['allowed_ips'] != '') ? explode(",", $admin['allowed_ips']) : array();

# Let's load up our language file, create a cache first...
$gamecp_lang = (isset($config['gamecp_lang'])) ? $config['gamecp_lang'] : 'en';
$lang_file = './includes/language/lang_' . $gamecp_lang . '.xml';
if (file_exists($lang_file)) {
    $xml = simplexml_load_file($lang_file);

    foreach ($xml->translations->string as $key => $string) {
        $lang_key = trim((string)$string->attributes()->key);
        $lang_value = (string)$string;
        $lang[$lang_key] = $lang_value;
    }
}

# Set default options
#date_default_timezone_set("EST");

# Just to make sure nothing goes wrong...
setlocale(LC_ALL, 'en_US.UTF8');

$onload = '';
$vbpath = '';
$index = '';
$out = '';
$mainincludes = '';

$isuser = false;
$notuser = true;
$exit_login = false;

# Get the current script name for 'checking'
$scripts = $_SERVER['PHP_SELF'];
$scripts = explode(chr(47), $scripts);
$this_script = $scripts[count($scripts) - 1];

# Do login check (?)
$cookiedata = (isset($_COOKIE["gamecp_userdata"])) ? $_COOKIE["gamecp_userdata"] : '';

# Set/Get Variables required
$ip = GetHostByName(getip());

# Or check variables :D
$out = '';
$title = '';
$exit_message = '';
$userdata = array();
$userdata['email'] = '';
$userdata['pin'] = '';
$userdata['status'] = false;
$userdata['username'] = 'Guest';
$userdata['serial'] = '-1';
$userdata['credits'] = '';
$userdata['createtime'] = '';
$userdata['lastconnectip'] = '';
$userdata['points'] = 0;
$userdata['vote_points'] = 0;
$userdata['rebirthpoints'] = 0;
$userdata['ip'] = getip();

# Okay, since we need this to be secure, lets kill the script if
# out salt is not set, okay?
if (!isset($config['security_salt']) or empty($config['security_salt'])) {
    quick_msg("Cannot run the script without the security_salt set to a value!");
}

# Check to see if user is logged in
if ($cookiedata != "") {
    $cookieex = explode('|', $cookiedata);
    $cookie_username = antiject(trim($cookieex[0]));
    $cookie_password = antiject(trim($cookieex[1]));

    if (!preg_match(REGEX_USERNAME, $cookie_username)) {
        $exit_login = true;
        $exit_message = '<p style="text-align: center; font-weight: bold;">Invalid login usage supplied!</p>';
        logoutUser();
    }

    if ($exit_login != true) {
        connectuserdb();
        $login_sql = "SELECT
		AccountName = CAST(L.id AS varchar(255)),
		Password = CAST(L.Password AS varchar(255)),
		L.EMail,L.pin, U.Serial, U.CreateTime, U.LastConnectIP, U.lastlogintime, U.lastlogofftime,
		U.uilock as uilock
		FROM
			" . TABLE_LUACCOUNT . " AS L
			INNER JOIN
			tbl_UserAccount AS U
			ON L.id = U.id
		WHERE
			L.id = convert(binary,'" . $cookie_username . "')";
        if (!($query_result = mssql_query($login_sql))) {
            $exit_login = true;
            $exit_message = '<p style="text-align: center; font-weight: bold;">SQL Error while trying to obtain user information</p>';
        }

        connectbillingdb();
        $billing_sql = "SELECT Cash
        FROM 
            tbl_user where UserID = '".$cookie_username."'";
			
		
        $billing_sql2 = "SELECT EndDate, BillingType
        FROM 
            tbl_personal_billing where ID = convert(binary,'".$cookie_username."')";

        if (!($query_result2 = mssql_query($billing_sql))) {
            $exit_login = true;
            $exit_message = '<p style="text-align: center; font-weight: bold;">SQL Error while trying to obtain user BILLING information</p>';
        }          

		if (!($query_result3 = mssql_query($billing_sql2))) {
            $exit_login = true;
            $exit_message = '<p style="text-align: center; font-weight: bold;">SQL Error while trying to obtain user BILLING information</p>';
        }  		

        if (!($row = mssql_fetch_array($query_result)) || !($row2 = mssql_fetch_array($query_result2)) || !($row3 = mssql_fetch_array($query_result3)) ) {
            $exit_login = true;
            $exit_message = '<p style="text-align: center; font-weight: bold;">' . _l('Unable to find your user information!') . '</p>';

            # HOOK: User not found
            @include('./includes/hook/gamecp_common-user_not_found.php');
            # HOOK;

            # Must kill/destory this cookie, its bad
            logoutUser();
        }


    }

    # No errors? Login then m8!
    if ($exit_login != true) {
        connectuserdb();
        # Get our user name from the database
        $userdata['username'] = antiject(trim($row['AccountName']));
        $userdata['password'] = antiject(trim($row['Password']));

        $password_data = md5($userdata['username']) . $ip . sha1(md5($userdata['password'] . $config['security_salt']));

        # HOOK: User not found
        @include('./includes/hook/gamecp_common-pre_login_validation.php');
        # HOOK;
        
       //start frans code//
		
		$session_userdata = explode('|', $_SESSION['gamecp_userdata']);
		$session_username = antiject(trim($session_userdata[0]));
		$session_password = antiject(trim($session_userdata[1]));
		
		/**
		if (!preg_match(REGEX_USERNAME, $session_username)) {
			$exit_login = true;
			$exit_message = '<p style="text-align: center; font-weight: bold;">Invalid login usage supplied!</p>';
			logoutUser();
		}
		**/
		
		if($session_username != $cookie_username || $session_password != $password_data)
		{
			$exit_login = true;
			$exit_message = '<p style="text-align: center; font-weight: bold;">Relogin Please</p>';
			logoutUser();
		}
		
		//end frans code//

        # If the user is logged in, is this the correct user?
        if ($cookie_username == $userdata['username'] && $cookie_password == $password_data) {
            // User is logged in!
            $isuser = true;
            $notuser = false;

            // Set user data we would need for the logged in user
            $userdata['serial'] = $row['Serial'];
            $userdata['email'] = $row['EMail'];
            $userdata['pin'] = $row['pin'];
            $userdata['createtime'] = strtotime($row['CreateTime']);
            $userdata['lastconnectip'] = $row['LastConnectIP'];
            $userdata['lastlogintime'] = $row['lastlogintime'];
            $userdata['lastlogofftime'] = $row['lastlogofftime'];
            $userdata['billingend'] = $row3['EndDate'];
            $userdata['billingstat'] = $row3['BillingType'];
            $userdata['billingcash'] = $row2['Cash'];
            
			$userdata['loginstate'] = ($userdata['lastlogintime'] > $userdata['lastlogofftime'] ) ? true : false;

            $t_login = strtotime($userdata['lastlogintime']);
            $t_logout = strtotime($userdata['lastlogofftime']);

            if ($t_login <= $t_logout) {
                $userdata['status'] = false;
            } else {
                $userdata['status'] = true;
            }
            /*
            if($userdata['status'] == true)
            {
			    $out = '<p style="text-align: center; font-weight: bold;">Your Account Currently Online, Please Logoff for access GameCP</p>';
                logoutUser();
            }*/

            // Wait...did you log into the game cp?
            if ($userdata['serial'] == '') {
                logoutUser();
            }

            # Query the user baen list
            $query_ban = mssql_query("SELECT nAccountSerial FROM tbl_UserBan WHERE nAccountSerial = '" . $userdata['serial'] . "'");

            # Has the user been banned?
            if ($userdata['serial'] != "" && mssql_num_rows($query_ban) > 0 && !in_array($userdata['username'], $super_admin)) {
                $isuser = false;
                $notuser = true;
                $out .= '<p style="text-align: center; font-weight: bold;">' . _l('Your account has been blocked! Please contact an Administrator.') . '</p>';

                # HOOK: User not found
                @include('./includes/hook/gamecp_common-user_banned.php');
                # HOOK;

                $_SESSION = array(); // destroy all $_SESSION data
                setcookie("gamecp_userdata", "", time() - 3600, '/');
                if (isset($_COOKIE["gamecp_userdata"])) {
                    unset($_COOKIE["gamecp_userdata"]);
                }
                session_destroy();
                gamecp_log(4, $userdata['username'], "GAMECP - LOGIN - Blocked account user tried to login", 1);
            }
            mssql_free_result($query_ban);

            // Okay so now lets get the user data from the Game CP DB
            connectgamecpdb();
            $user_points_sql = "SELECT user_points,user_vote_points,user_rebirth_points FROM gamecp_gamepoints WHERE user_account_id = '" . $userdata['serial'] . "'";
            if (!($user_points_result = mssql_query($user_points_sql))) {
                echo "Unable to select query the Game Points table";
                exit;
            }

            // Permissions
            $permission_query = "SELECT admin_permission FROM gamecp_permissions WHERE admin_serial = '" . $userdata['serial'] . "'";
            $permission_query = mssql_query($permission_query);
            if (!($user_access = @mssql_fetch_array($permission_query))) {
                $user_access = false;
            }
            mssql_free_result($permission_query);

            $userpoints = mssql_fetch_array($user_points_result);
            if (mssql_num_rows($user_points_result) <= 0) {
                $user_points_sql = "INSERT INTO gamecp_gamepoints (user_account_id, user_points) VALUES ('" . $userdata['serial'] . "', '" . $config['register_gamepoints'] . "')";
                if (!($user_points_result = mssql_query($user_points_sql))) {
                    echo "Unable to insert query the Game Points table";
                    exit;
                }

                $userdata['points'] = $config['register_gamepoints'];
                $userdata['vote_points'] = 0;
                $userdata['rebirthpoints'] = 0;
            } else {
                $userdata['points'] = $userpoints['user_points'];
                $userdata['vote_points'] = $userpoints['user_vote_points'];
                $userdata['rebirthpoints'] = $userpoints['user_rebirth_points'];
                # HOOK: User not found
                @include('./includes/hook/gamecp_common-user_load_points.php');
                # HOOK;
            }
            @mssql_free_result($user_points_result);

            // Sometimes they might have not doanted, so its blank
            if ($userdata['points'] == "") {
                $userdata['points'] = 0;
            }
            
            // Sometimes they might have not doanted, so its blank
            if ($userdata['rebirthpoints'] == "") {
                $userdata['rebirthpoints'] = 0;
            }

            // Check to make sure user logged into the game cp at least once
            if ((isset($config['security_require_in_game_login']) && $config['security_require_in_game_login'] == 1) && $row['uilock'] == 0) {
                $out .= '<p style="text-align: center; font-weight: bold; text-decoration: underline;">' . _l('Please log into the game at least ONCE and setup your Fireguard Password.') . '</p>';
                logoutUser();
            }

            connectgamecpdb();

            # HOOK: User not found
            @include('./includes/hook/gamecp_common-finish_user_login.php');
            # HOOK;

            // For security, unset the password!
            unset($userdata['password']);

        } else {
            $notuser = true;
            $isuser = false;
            if (isset($userdata['password'])) {
                unset($userdata['password']);
            }
        }

    } else {
        $out .= $exit_message;
    }

} else {
    $isuser = false;
}

# Are we a super-m--admin?
if ($isuser == true && in_array($userdata['username'], $super_admin)) {
    if (!empty($allowed_ips)) {
        if (checkIP($userdata['ip'], $allowed_ips)) {
            $is_superadmin = true;
        } else {
            $out .= '<p style="text-align: center; font-weight: bold;">You do not have the necessary permissions log into this account. This has been logged.</p>';
            gamecp_log(5, $userdata['username'], "GAMECP - LOGIN - FAILED TO LOG INTO SUPER ADMIN ACCOUNT. IP RESTRICTED", 1);

            $_SESSION = array(); // destroy all $_SESSION data
            setcookie("gamecp_userdata", "", time() - 3600);
            if (isset($_COOKIE["gamecp_userdata"])) {
                unset($_COOKIE["gamecp_userdata"]);
            }
            $notuser = true;
            $isuser = false;
            session_destroy();
            if (isset($userdata['password'])) {
                unset($userdata['password']);
            }
            $is_superadmin = false;
        }
    } else {
        $is_superadmin = true;
    }
} else {
    $is_superadmin = false;
    error_reporting(0);
}

# Security token, will be used for sessions mearly
$securitytoken_raw = sha1($userdata['serial'] . sha1($config['security_salt']) . sha1($config['security_salt']));
$securitytoken = time() . '-' . sha1(time() . $securitytoken_raw);

# Check to see if we are using recaptcha
if (isset($config['security_recaptcha_enable']) && $config['security_recaptcha_enable'] == 1) {
    require_once('./includes/main/recaptchalib.php');

    // Get a key from http://recaptcha.net/api/getkey
    $publickey = (isset($config['security_recaptcha_public_key'])) ? $config['security_recaptcha_public_key'] : '';
    $privatekey = (isset($config['security_recaptcha_private_key'])) ? $config['security_recaptcha_private_key'] : '';

    # the response from reCAPTCHA
    $resp = null;
    # the error code from reCAPTCHA, if any
    $error = null;
}

# Special userdata stuff to be used in templates
$user_points = number_format($userdata['points']);
$user_vote_points = number_format($userdata['vote_points']);
$user_rebirth_points = number_format($userdata['rebirthpoints']);


## // This is put here because we want this global // ##
# Lets get our vote sites data first
# We will put it into an array so that it can be used later
# Btw, this is first because we will kill the page if no results were returned
## ////////////////////////////////////////////////// ##
connectgamecpdb();
$vote = array();
$vote_sql = "SELECT vote_id, vote_site_name, vote_site_url, vote_site_image, vote_reset_time FROM gamecp_vote_sites";
if (!($vote_result = mssql_query($vote_sql))) {
    $exit_stage_0 = true;
    $show_form = false;
    $page_info .= '<p style="text-align: center; font-weight: bold;">SQL Error while trying to obtain vote sites data</p>';
}
while ($row = @mssql_fetch_array($vote_result)) {
    $vote[] = $row;
}
mssql_free_result($vote_result);

$vote_page = '';
for ($i = 0; $i < count($vote); $i++) {
    $vote_page .= '			<a href="javascript:voteScript(\'' . $vote[$i]['vote_id'] . '\',\'' . $vote[$i]['vote_site_name'] . '\')" title="' . $vote[$i]['vote_site_name'] . '"><img src="' . $vote[$i]['vote_site_image'] . '" alt="' . $vote[$i]['vote_site_name'] . '" border="0"></a>' . "\n";
}




# HOOK: User not found
@include('./includes/hook/gamecp_common-end_of_file.php');
# HOOK;
?>