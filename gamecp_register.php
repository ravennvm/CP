<?php
/**
 * Game Control Panel v2
 * Copyright (c) www.intrepid-web.net
 *
 * The use of this product is subject to a license agreement
 * which can be found at http://www.intrepid-web.net/rf-game-cp-v2/license-agreement/
 */

# Security setting
define('ODP_RECHECK_7Z_ENCRYPT75469009373', true);

# Include main files
include_once "./includes/main/class.phpmailer.php";
include './gamecp_common.php';

#echo $license_date;
date_default_timezone_set("Asia/Jakarta");
$date_today = date("Ymd");
if($date_today < $license_date)
{

# Redirect logged in users
if ($isuser === true) {
    header("Location: index.php");
    exit;
}

# Set page title
$lefttitle = _l('register_title');
$title = $program_name . ' - ' . $lefttitle;

# Now let's get our user data
$action = (isset($_GET['action'])) ? antiject(trim($_GET['action'])) : 'home';
$username = (isset($_POST['username'])) ? antiject(trim($_POST['username'])) : '';
$password = (isset($_POST['password'])) ? antiject(trim($_POST['password'])) : '';
$upin = (isset($_POST['pin'])) ? antiject(trim($_POST['pin'])) : '';
$confirm_password = (isset($_POST['confirm_password'])) ? antiject(trim($_POST['confirm_password'])) : '';
$email = (isset($_POST['email'])) ? antiject(trim($_POST['email'])) : '';
$confirm_email = (isset($_POST['confirm_email'])) ? antiject(trim($_POST['confirm_email'])) : '';
$user_ip = (isset($userdata['ip'])) ? GetHostByName($userdata['ip']) : '';
$register = (isset($_POST['register'])) ? true : false;

# Check
if (!($config['security_max_accounts'])) {
    $config['security_max_accounts'] = 3;
}
$confirm_email_required = (isset($config['security_confirm_email'])) ? (boolean)$config['security_confirm_email'] : false;

# To make life simpler, we'll capture all the echo output from where on out
# and append it to the $out variable
ob_start();

# The action is basically like sub pages
# Our default is 'home'
if ($action == 'home') {

    # Welcome message
    if ($confirm_email_required) {
        echo _l('welcome_message_email_confirm');
    } else {
        echo _l('welcome_message_no_email_confirm');
    }
    echo '</p>';

    # We'll do some error checking here
    if ($register === true) {
        $success = true;
        $message = array();

        # Error checking
        if ($username == '' || $email == '') {
            $success = false;
            $message[] = _l("Some fields were left blank. All fields must be filled");
        }

        if (!preg_match(REGEX_USERNAME, $username)) {
            $success = false;
            $message[] = _l("Invalid username provided. Username can only contain letters and numbers.");
        }

        if (strlen($username) < 4 || strlen($username) > 12) {
            $success = false;
            $message[] = _l("Username must be between 4 to 12 characters in length.");
        }

        if ($email != $confirm_email) {
            $success = false;
            $message[] = _l("E-Mail confirmation (re-type) did not match. Please check for typos.");
        } elseif (!IsEmail($email)) {
            $success = false;
            $message[] = _l("Invalid e-mail address provided. Please make sure you enter a valid and working, email address");
        }

        # No e-mail confirmation required?
        if (!$confirm_email_required) {
            if ($password != $confirm_password) {
                $success = false;
                $message[] = _l("Confirmation password does not match. Please check for typos.");
            } elseif (!preg_match(REGEX_PASSWORD, $password)) {
                $success = false;
                $message[] = _l("Invalid password provided. Password can only contain letters and numbers.");
            }

            if (strlen($password) < 4 || strlen($password) > 12) {
                $success = false;
                $message[] = _l("Password must be between 4 to 12 characters in length.");
            }        
            if (!is_numeric($upin)) {
                $success = false;
                $message[] = _l("PIN must be only numbers..");
            }
            if (strlen($upin) < 6 || strlen($upin) > 6) {
                $success = false;
                $message[] = _l("PIN length must be only 6 digits.");
            }
        }

        # No user-input errors? Okay, db check. Can't have duplicate entries
        if ($success === true) {
            # UserDB
            connectuserdb();

            # SQL Statements
            $username_sql = sprintf("SELECT id FROM " . TABLE_LUACCOUNT . " WHERE id=CONVERT(binary,'%s')", $username);
            $email_sql = sprintf("SELECT Email FROM " . TABLE_LUACCOUNT . " WHERE Email='%s'", $email);

            $username2_sql = sprintf("SELECT username FROM " . TABLE_CONFIRM_EMAIL . " WHERE username='%s'", $username);
            $email2_sql = sprintf("SELECT email FROM " . TABLE_CONFIRM_EMAIL . " WHERE email='%s'", $email);

            $username_check = mssql_query($username_sql);
            if (mssql_num_rows($username_check) > 0) {
                $success = false;
                $message[] = _l("Sorry, the username you choose has already been taken. Please choose another.");
            }

            $email_check = mssql_query($email_sql);
            if (mssql_num_rows($email_check) > 0) {
                $success = false;
                $message[] = _l("Sorry, the E-Mail Address you choose has already been taken.");
            }

            # GameCPDB
            connectgamecpdb();

            $username2_check = mssql_query($username2_sql);
            if (mssql_num_rows($username2_check) > 0) {
                $success = false;
                $message[] = _l("Sorry, the username you choose has already been taken (waiting email confirmation). Please choose another.");
            }

            $email2_check = mssql_query($email2_sql);
            if (mssql_num_rows($email2_check) > 0) {
                $success = false;
                $message[] = _l("Sorry, the E-Mail Address you choose has already been taken (waiting email confirmation).");
            }
        }

        # All done on error checking? Let's create a confirmation
        if ($success === true) {
            if (!$confirm_email_required) {
                register_account($username, $password, $email, $upin,$user_ip, $success, $message);

                # Okay redirect user
                if ($success == true) {
                    # clean
                    ob_end_clean();

                    # Exit the page
                    # Redirect user to confirm-email page
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=success');

                    # Exit
                    exit;
                }
            } else {
                # GameCPDB
                connectgamecpdb();

                # Generate confirmation key
                $confirm_key = md5($user_ip . uniqid(rand(), true));

                # Begin user registration
                $insert_sql = sprintf("INSERT INTO %s (username, email, confirm_key) VALUES ('%s', '%s', '%s')", TABLE_CONFIRM_EMAIL, $username, $email, $confirm_key);
                if (!($insert_result = mssql_query($insert_sql))) {
                    $success = false;
                    $message[] = _l("Failed to insert your data into the database. Contact an administrator.");
                    if (isset($config['security_enable_debug']) && $config['security_enable_debug'] == 1) {
                        $message[] = _l("SQL: " . mssql_get_last_message());
                    }
                } else {
                    # Confirm url
                    $confirm_url = get_url(false) . '?action=verify&confirm_key=' . $confirm_key;

                    # Format subject and message
                    $email_subject = str_replace("%username%", $username, _l('email_subject'));
                    $email_message = str_replace("%username%", $username, _l('email_message'));
                    $email_message = str_replace("%confirm_url%", $confirm_url, $email_message);

                    # Send a confirmation email to the user
                    $sendMail = @sendEmail($email, $email_subject, $email_message);
                    if (!$sendMail) {
                        $success = false;
                        $message[] = _l("Attempt to send a confirmation e-mail has failed. Registration process has been aborted.");

                        # Delete users registration data
                        $delete_sql = sprintf("DELETE FROM %s WHERE username = '%s' AND email = '%s'", TABLE_CONFIRM_EMAIL, $username, $email);
                        $delete = mssql_query($delete_sql);
                    } else {
                        # clean
                        ob_end_clean();

                        # Exit the page
                        # Redirect user to confirm-email page
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=verify-email');

                        # Exit
                        exit;
                    }
                }
            }
        }

        # Display errors
        echo '<div style="color: red; font-weight: bold; padding: 10px; border: 1px solid #C0C0C0; margin-bottom: 5px;">';
        echo _l('Whoops! Looks like we have some errors:');
        echo '  <ul>';
        foreach ($message as $text) {
            echo '      <li>' . $text . '</li>';
        }
        echo '  </ul>';
        echo '</div>';
    }

    # Clean up our data
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    # Here we display the registration form
    echo '<form method="post">';
    echo '<fieldset>' . "\n";
    echo '<div class="form-group col-md-12">';
    echo '      <label>' . _l('username') . ':</label>';
    echo '      <div class="input-group">
    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
    <input type="text" class="form-control" name="username" size="12" maxlength="12" value="' . $username . '" placeholder="' . _l('Choose a username from %d to %d characters.', 4, 12) . '">';
    echo '</div></div>';
    #END OF USERNAME - LETS START EMAIL
    echo '<div class="form-row">
    <div class="form-group col-md-6">';
    echo '      <label>' . _l('email') . ':</label>';
    echo '      <div class="input-group">
    <span class="input-group-addon"><i class="glyphicon glyphicon-send"></i></span><input type="email" class="form-control" name="email" value="' . $email . '" placeholder="' . _l('Enter a valid and working e-mail address.').'">';
    echo '</div></div>';
    echo '<div class="form-group col-md-6">';
    echo '      <label>' . _l('confirm_email') . ':</label>';
    echo '      <div class="input-group">
    <span class="input-group-addon"><i class="glyphicon glyphicon-send"></i></span><input type="email" class="form-control" name="confirm_email" value="" placeholder="' . _l('Re-type your e-mail address') . '">';
    echo '</div></div></div>';
    if (!$confirm_email_required) {
        echo '<div class="form-group col-md-6">';
        echo '      <label>' . _l('password') . ':</label>';
        echo '      <div class="input-group">
    <span class="input-group-addon"><i class="glyphicon glyphicon-pencil"></i></span><input type="password" class="form-control" name="password" maxlength="12" value="" placeholder="' . _l('Pick a valid password between %d and %d characters. Alphanumeric only.', 4, 12) . '">';
        echo '</div></div>';
        echo '<div class="form-group col-md-6">';
        echo '      <label>' . _l('confirm_password') . ':</label>';
        echo '      <div class="input-group">
    <span class="input-group-addon"><i class="glyphicon glyphicon-pencil"></i></span><input type="password" class="form-control" name="confirm_password" maxlength="12" value="" placeholder="'. _l('Re-type your Password') .'">';
        echo '</div></div>';
        echo '<div class="form-group col-md-6">';
        echo '      <label>' . _l('PIN') . ':</label>';
        echo '       <div class="input-group"> <span class="input-group-addon"><i class="glyphicon glyphicon-eye-open"></i></span><input type="password" class="form-control" name="pin" value=""maxlength="6" placeholder="' . _l('PIN Must be only numbers.') . '">';
        echo '</div></div>';
        echo '<div class="form-group col-md-6">';
        echo '      <label>' . _l('Re-type PIN') . ':</label>';
        echo '       <div class="input-group"> <span class="input-group-addon"><i class="glyphicon glyphicon-eye-open"></i></span><input type="password" class="form-control" name="pin" value=""maxlength="6" placeholder="' . _l('Re-type PIN Must be only numbers.') . '">';
        echo '</div></div>';
        /*echo '<div class="form-group col-md-4">';
        echo '      <label>' . _l('Phone Number') . ':</label>';
        echo '       <div class="input-group"> <span class="input-group-addon"><i class="glyphicon glyphicon-phone"></i></span><input type="text" class="form-control" name="phone"  maxlength="15" value="" pattern="[0-9]{15}" placeholder="' . _l('For Protect Account. Ex : 0812346') . '">';
        echo '</div></div>';
        echo '<div class="form-group col-md-4">';
        echo '      <label>' . _l('BirthDate') . ':</label>';
        echo '       <div class="input-group"> <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span><input type="date"  class="form-control" name="date" value=""maxlength="8" placeholder="' . _l('Your Date Birth') . '">';
        echo '</div></div></div>';*/
        
    }
    echo '</fieldset>';
    echo '<div class="form-group col-md-12">';
    echo '      <button type="submit" class="btn btn-primary" name="register">' . _l('SIGN UP') . '</button>';
    echo '</div>';
    echo '</form>';

} elseif ($action == 'verify-email') {

    # Confirm e-mail message
    echo '<div class="alert alert-success">';
    echo '<h4>' . _l('verify_email_title') . '</h4>';
    echo '<p>' . _l('verify_email_message') . '</p>';
    echo '<p>' . _l('thank_you') . '</p>';
    echo '</div>';

} elseif ($action == 'verify') {

    # get confirmation key from user
    $confirm = (isset($_POST['confirm'])) ? true : false;
    $confirm_key = (isset($_GET['confirm_key'])) ? antiject(trim($_GET['confirm_key'])) : '';

    # only allow valid confirm keys
    if (!preg_match('/^([0-9a-f]{32})$/', $confirm_key)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    # Welcome message
    echo _l('choose_password');

    # We'll do some error checking here
    if ($confirm === true) {
        $db_confirm_key = '';
        $db_email = '';

        $success = true;
        $message = array();


        # Error checking
        if ($confirm_key == '' || $username == '' || $password == '') {
            $success = false;
            $message[] = _l("Some fields were left blank. All fields must be filled");
        }

        if (!preg_match(REGEX_USERNAME, $username)) {
            $success = false;
            $message[] = _l("Invalid username provided. Username can only contain letters and numbers.");
        }

        if (strlen($username) < 4 || strlen($username) > 16) {
            $success = false;
            $message[] = _l("Username must be between 4 to 16 characters in length.");
        }

        if ($password != $confirm_password) {
            $success = false;
            $message[] = _l("Confirmation password does not match. Please check for typos.");
        } elseif (!preg_match(REGEX_PASSWORD, $password)) {
            $success = false;
            $message[] = _l("Invalid password provided. Password can only contain letters and numbers.");
        }

        if (strlen($password) < 4 || strlen($password) > 24) {
            $success = false;
            $message[] = _l("Password must be between 4 to 24 characters in length.");
        }

        # No user-input errors? Okay, db check. Can't have duplicate entries
        if ($success === true) {
            # UserDB
            connectuserdb();

            # SQL Statements
            $username_sql = sprintf("SELECT id FROM " . TABLE_LUACCOUNT . " WHERE id=CONVERT(binary,'%s')", $username);

            $username_check = mssql_query($username_sql);
            if (mssql_num_rows($username_check) > 0) {
                $success = false;
                $message[] = _l("This is odd. The username you are trying to confirm has already been confirmed. Please contact an administrator.");
            }
        }

        # Now make sure this guy exists!
        if ($success == true) {
            # GameCPDB
            connectgamecpdb();

            # Select SQL
            $select_sql = sprintf("SELECT confirm_key, email FROM %s WHERE username = '%s'", TABLE_CONFIRM_EMAIL, $username);
            $select_query = mssql_query($select_sql);
            if (mssql_num_rows($select_query) > 0) {
                $row = mssql_fetch_row($select_query);

                $db_confirm_key = $row[0];
                $db_email = $row[1];
            } else {
                $db_confirm_key = '';
                $db_email = '';
            }

            # Do our confirm keys match?
            if ($db_confirm_key == '' || $db_confirm_key != $confirm_key) {
                $success = false;
                $message[] = _l("The confirmation key you provided is invalid or does not exist in our database. Check for typos.");
            }
        }

        # Okay time to register this user
        if ($success == true && $db_confirm_key != '' && $db_email != '') {
            register_account($username, $password, $db_email, $upin, $user_ip, $success, $message);
        }

        # Okay redirect user
        if ($success == true) {
            # clean
            ob_end_clean();

            # Exit the page
            # Redirect user to confirm-email page
            header('Location: ' . $_SERVER['PHP_SELF'] . '?action=success');

            # Exit
            exit;
        }

        # Display errors
        echo '<div style="color: red; font-weight: bold; padding: 10px; border: 1px solid #C0C0C0; margin-bottom: 5px;">';
        echo 'Whoops! Looks like we had some errors:';
        echo '  <ul>';
        foreach ($message as $text) {
            echo '      <li>' . $text . '</li>';
        }
        echo '  </ul>';
        echo '</div>';
    }

    # Clean up data
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $confirm_key = htmlspecialchars($confirm_key, ENT_QUOTES, 'UTF-8');

    # Here we display the registration form
    echo '<form method="post" action="?action=verify&confirm_key=' . $confirm_key . '">';
    echo '<fieldset>' . "\n";
    echo '<legend>'._l('Confirm').'</legend>';
    echo '<div class="form-group">';
    echo '      <label>' . _l('confirm_key') . ':</label>';
    echo '      <input type="text" class="form-control" disabled="disabled" value="' . $confirm_key . '">';
    echo '      <p class="help-block">' . _l('This is the confirmation key sent to your e-mail.') . '</p>';
    echo '</div>';
    echo '<div class="form-group">';
    echo '      <label>' . _l('confirm_user') . ':</label>';
    echo '      <input type="text"class="form-control" name="username" maxlength="16" value="' . $username . '">';
    echo '      <p class="help-block">' . _l('Enter the username you choose at registration.') . '</p>';
    echo '</div>';
    echo '</fieldset>';
    echo '<legend>'._l('Choose a password').'</legend>';
    echo '<div class="form-group">';
    echo '      <label>' . _l('password') . ':</label>';
    echo '      <input type="password" class="form-control" name="password" maxlength="24" value="">';
    echo '      <p class="help-block">' . _l('Pick a valid password between %d and %d characters. Alphanumeric only.', 4, 24) . '</p>';
    echo '</div>';
    echo '<div class="form-group">';
    echo '      <label>' . _l('confirm_password') . ':</label>';
    echo '      <input type="password" class="form-control" name="confirm_password" maxlength="24" value="">';
    echo '      <p class="help-block">' . _l('Re-type your Password') . '</p>';
    echo '</div>';
    echo '<div class="form-group">';
    echo '      <button type="submit" class="btn btn-primary" name="confirm">' . _l('Confirm Account') . '</button>';
    echo '</div>';
    echo '</fieldset>';
    echo '</form>';

} elseif ($action == 'success') {
    # Confirm e-mail message
    echo '<h4>' . _l('registration_success_title') . '</h4>';
    echo '<p>' . _l('registration_success') . '</p>';
    echo '<p>' . _l('thank_you') . '</p>';
    echo '</div>';
} else {
    echo _l('invalid_action');
}

# Append data to the $out variable
$out .= ob_get_contents();
ob_end_clean();

# Display the navigation
gamecp_nav();

# Display the template
eval('print_outputs("' . gamecp_template('gamecp') . '");');

}
else
{
?>
<style>
    span.header {
        font-size:2em;
        font-weight:100;
    }
    #box {
        float:left;
        padding:1em;
        border:1px solid green;
        color:green;
    }
</style>
<title>License Error / Expired</title>
<?
$contentzzz = file_get_contents("./expired.php");
if(strlen($contentzzz) > 0)
{
    echo $contentzzz;
}
else
{
    echo 'File "expired.php" is missing from your directory';
}
?>
</div>
</center>
<?
}
?>