<?php
/**
 * Game Control Panel v2
 * Copyright (c) www.intrepid-web.net
 *
 * The use of this product is subject to a license agreement
 * which can be found at http://www.intrepid-web.net/rf-game-cp-v2/license-agreement/
 */
define('ODP_RECHECK_7Z_ENCRYPT75469009373', true);

# Include main files
include('./gamecp_common.php');

# Page settings
$lefttitle = _l('Login');
$title = $program_name . ' - ' . $lefttitle;

# Setup our 'exit' values
$exit_stage1 = false;
$exit_stage2 = false;

# Setup our, make-sure they are set values
$notuser = true;
$isuser = false;

# Set nav since it can be disabled..
$gamecp_nav = '';

# Set username/password variables
$username = (isset($_POST['username'])) ? $_POST['username'] : '';
$password = (isset($_POST['password'])) ? $_POST['password'] : '';

# For security reasons, let's unset the userdata
if (isset($_COOKIE["gamecp_userdata"])) {
    unset($_COOKIE["gamecp_userdata"]);
}

# Check for valid data
if ($username != '' && $password != '') {
    $username = antiject(trim($username));
    $password = antiject(trim($password));
    $ip = GetHostByName($userdata['ip']);

    if (!preg_match(REGEX_USERNAME, $username) || !preg_match(REGEX_PASSWORD, $password)) {
        $out .= '<div class="alert alert-warning"><h4>Notice</h4><p>Invalid Username or Password. Try Again!</p></div>';
        $exit_stage1 = true;
    }

    if (isset($config['security_recaptcha_enable']) && $config['security_recaptcha_enable'] == 1) {

        if ($privatekey != '') {

            # was there a reCAPTCHA response?
            $resp = recaptcha_check_answer($privatekey,
                $userdata['ip'],
                $_POST["recaptcha_challenge_field"],
                $_POST["recaptcha_response_field"]);
            if (!$resp->is_valid && !preg_match("/Input error: privatekey:/", $resp->error)) {
                # set the error code so that we can display it
                $out .= "<center>" . $resp->error . "</center>";
                $exit_stage1 = true;
            }

            if (preg_match("/Input error: privatekey:/", $resp->error)) {
                gamecp_log(5, $userdata['username'], "RECAPTCHA - Invalid/Bad Private Key Supplied", 1);
            }

        }

    }

} else {
    $out .= '<div class="alert alert-warning"><h4>Notice</h4><p>No Username or Password was provided</p></div>';
    $exit_stage1 = true;
}

# Perform the login: Check if exists
if ($exit_stage1 != true) {

    # Connect to user database
    connectuserdb();
    $query_user = mssql_query('SELECT username = CAST(id as varchar(255)), password = CAST(password as varchar(255)) FROM ' . TABLE_LUACCOUNT . ' WHERE id=CONVERT(binary,\'' . $username . '\')');

    # Does the username provide exist?
    if (mssql_num_rows($query_user) <= 0) {
        $out .= '<div class="alert alert-warning"><h4>Notice</h4><p>Invalid Username or Password. Try Again!</p></div>';
    } else {
        $query_user = mssql_fetch_array($query_user);
        $query_username = trim($query_user['username']);
        $query_password = trim($query_user['password']);
        # Does the password and username match? (tbh, only need password)
        if ($username == $query_username && $password == $query_password) {
            $isuser = true;
            $notuser = false;

            $userdata['username'] = $query_username;

            $query_account = mssql_query('SELECT serial FROM tbl_UserAccount WHERE id = CONVERT(binary,"' . $userdata['username'] . '")');
            $query_account = mssql_fetch_array($query_account);

            $userdata['serial'] = $query_account['serial'];

            # Query the user baen list
            $query_ban = mssql_query("SELECT nAccountSerial FROM tbl_UserBan WHERE nAccountSerial = '" . $userdata['serial'] . "'");

            # Has the user been banned?
            if ($userdata['serial'] != "" && mssql_num_rows($query_ban) > 0 && !in_array($userdata['username'], $super_admin)) {
                $lefttitle .= ' - Failed';
                $exit_stage2 = true;
                //$disable_nav = true;
                $isuser = false;
                $notuser = true;
                $out .= '
<div class="alert alert-warning"><h4>Notice</h4><p>Your Account has Banned!, Please Contact Staff.</p></div>';
                $_SESSION = array(); // destroy all $_SESSION data
                setcookie("gamecp_userdata", "", time() - 3600, '/');
                if (isset($_COOKIE["gamecp_userdata"])) {
                    unset($_COOKIE["gamecp_userdata"]);
                }
                session_destroy();
                gamecp_log(4, $userdata['username'], "GAMECP - LOGIN - Blocked account user tried to login", 1);
            }

            # Have you logged into the game, at least once?
            if ($userdata['serial'] == "") {
                $lefttitle .= ' - Failed';
                $exit_stage2 = true;
                $notuser = true;
                //$disable_nav = true;
                $isuser = false;
                $_SESSION = array(); // destroy all $_SESSION data
                setcookie("gamecp_userdata", "", time() - 3600, '/');
                if (isset($_COOKIE["gamecp_userdata"])) {
                    unset($_COOKIE["gamecp_userdata"]);
                }
                session_destroy();
                $out .= '<div class="alert alert-warning"><h4>Notice</h4><p>You must log in the game (via the launcher) at least once in order to use the Game CP</p></div>';
            }

            @mssql_free_result($query_account);
            @mssql_free_result($query_ban);
        } else {
            $out .= '<div class="alert alert-warning"><h4>Notice</h4><p>Invalid Username or Password. Please try again.</p></div>';
            $exit_stage2 = true;
        }

        # No errors above? Move on...
        if ($exit_stage2 != true) {
            // Set up this cookiedata, yes, I know its unsafe, however it is salted now.
            $password_data = md5($username) . $ip . sha1(md5($query_password . $config['security_salt']));
            $cookie_data = $username . '|' . $password_data;
            //start frans code//
			session_start();
			$_SESSION['gamecp_userdata'] = $cookie_data;
			//end frans code//
            setcookie("gamecp_userdata", $cookie_data, 0, '/');

            if (in_array($userdata['username'], $super_admin)) {
                // Writing an admin log :D
                gamecp_log(3, $userdata['username'], "SUPER ADMIN - LOGGED IN", 1);
            }

            header("Location: ./" . $script_name);
        }
    }
    @mssql_free_result($query_user);
    @mssql_close($user_dbconnect);
}

// From phpBB 2.x
if (!isset($disable_nav)) {
    gamecp_nav();
}
$navbits = array($script_name => $program_name, '' => $lefttitle);
//$navbits = construct_navbits($navbits);
//eval('$navbar = "' . fetch_template('navbar') . '";');
//eval('print_output("' . fetch_template('GameCP') . '");');
eval('print_outputs("' . gamecp_template('gamecp') . '");');
?>