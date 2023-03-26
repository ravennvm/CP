<?php
/**
* Game Control Panel v2
* Copyright (c) www.intrepid-web.net
*
* The use of this product is subject to a license agreement
* which can be found at http://www.intrepid-web.net/rf-game-cp-v2/license-agreement/
*/

# Set definations
define('THIS_SCRIPT', 'gamecp');
define('ODP_RECHECK_7Z_ENCRYPT75469009373', true);

# Include Main files
include('./gamecp_common.php');

# Draw the navigation bits
$navbits = array($config['gamecp_filename'] => $config['gamecp_programname']);

# Out...Obtain these variables...variables...
$do = (isset($_REQUEST['do'])) ? antiject($_REQUEST['do']) : '';
$forum_username = 'N/A';

#echo $license_date;
date_default_timezone_set("Asia/Jakarta");
$date_today = date("Ymd");
$date = date("d F Y",strtotime($date_today));
?>

<?

if($date_today < $license_date)
{
# Set a default page (not logged in)
if (($do == "") && ($notuser)) {
$lefttitle = $program_name;
$title = $program_name;
$navbits = array($script_name => $program_name, '' => $lefttitle);

// ------------------ QUERIES END KILLER ------------------ //

# HOOK: User not found
@include('./includes/hook/index-start-user_logged_out.php');
# HOOK;
$out .= '<div class="panel panel-default">
<div class="panel-heading">Login Account Panel</div>
<div class="panel-body">' . "\n";
$out .= '<form method="post" action="gamecp_login.php">' . "\n";
$out .= '<fieldset>';
$out .= '<legend>' . _l('Welcome to Game Panel') . '</legend>' . "\n";
$out .= '<div class="form-group">';
$out .= ' <label for="username">Username</label>';
$out .= ' <input name="username" type="text" class="form-control" id="username" placeholder="Enter your username">';
$out .= '</div>';
$out .= '<div class="form-group">';
$out .= ' <label for="password">Password</label>';
$out .= ' <input name="password" type="password" class="form-control" id="password" placeholder="Password">';
$out .= '</div>';
if (isset($config['security_recaptcha_enable']) && $config['security_recaptcha_enable'] == 1) {
$out .= recaptcha_get_html($publickey, $error);
}
$out .= '<button type="submit" class="btn btn-primary">Login</button>';
$out .= '</fieldset>';
$out .= '</form>' . "\n";
$out .= '<br>' . "\n";
$out .= _l('<h4><span style="color:red;text-align:center;">Register Account Click <a href="%s">Here</a>', 'gamecp_register.php') . '<br/></h4></span>' . "\n";
$out .= _l('<h4>Lost or forgot your password? Recover it <a href="%s">Here</a>', $script_name . '?do=user_passwordrecover') . '<br/></h4>'. "\n";
$out .= _l('<h4>Lost or forgot your fireguard? Recover it <a href="%s">Here</a>', $script_name . '?do=user_fireguardrecover') . "</h4>\n";
$out .= '</div>' . "\n";
$out .= '</div>' . "\n";
# HOOK: User not found
@include('./includes/hook/index-end-user_logged_out.php');
# HOOK;

# Set a default page (is logged in)
} elseif (($do == "") && ($isuser)) {
$lefttitle = _l('Welcome to Game Access');
$title = $program_name;
$navbits = array($script_name => $program_name, '' => $lefttitle);

# HOOK: User not found
@include('./includes/hook/index-start-user_logged_in.php');
# HOOK;
$usEmail = explode("@",$userdata['email']);
$out .= '<div class="panel panel-default">
<div class="panel-heading">Account Information Status</div>
<div class="panel-heading"><b><span style="color:red">PERINGATAN !!! Silahkan Ganti Password Secara Berkala Menghindari Hal-Hal Yang Tidak Diinginkan </span></div></b>
<div class="panel-body">' . "\n";
$out .= '<p><b>' . _l('Username') . ':</b> <i>' . $userdata['username']. '</i></p>' . "\n";
$out .= '<p><b>' . _l('Account E-Mail') . ':</b> <i>' . substr($usEmail[0],0,-5)."*****@".$usEmail[1]. '</i></p>' . "\n";
$out .= '<p><b>' . _l('Vote Points') . ':</b> <i>' . number_format($userdata['points']) . '</i></p>' . "\n";
$out .= '<p><b>' . _l('Rebirth Points') . ':</b> <i>' . number_format($userdata['rebirthpoints']) . '</i></p>' . "\n";
$out .= '<p><b>' . _l('Cash Points') . ':</b> <i>' . number_format($userdata['billingcash']) . '</i></p>' . "\n";
$out .= '<p><b>' . _l('Premium Status') . ':</b> <i>' . $userdata['billingend']. ' | ' . ($userdata['billingstat'] == 2 ? _l('RUNNING'): _l('EXPIRED')). '</i></p>' . "\n";
$out .= '<p><b>' . _l('Last Log In Time') . ':</b> <i>' . $userdata['lastlogintime'] . '</i></p>' . "\n";
$out .= '<p><b>' . _l('Last Log Off Time') . ':</b> <i>' . $userdata['lastlogofftime'] . '</i></p>' . "\n";
$out .= '<p><b>' . _l('Last Connect IP Address') . ':</b> <i>' . ($userdata['lastconnectip'] != 0 ? $userdata['lastconnectip'] : _l('None')) . '</i></p>' . "\n";
$out .= '<p><b>' . _l('Current State') . ':</b> <i>' . (($userdata['status']) ? _l('Online') : _l('Offline')) . '</i></p>' . "\n";
$out .= '<p><a href="./gamecp_logout.php" class="btn btn-primary">Sign Out</a></p>' . "\n";
$out .= '</div> </div>' . "\n";




# HOOK: User not found
@include('./includes/hook/index-end-user_logged_in.php');
# HOOK;
} else {
# Include the pages given by $do

// Security checks
$do = str_replace('.', '', $do);
$do = str_replace('\\', '', $do);
$do = str_replace('/', '', $do);
$do = trim($do);

if (!preg_match('/^([a-zA-Z0-9\-\_]+)$/', $do)) {
echo 'Invalid ' . $do;
exit;
}

# HOOK: User not found
@include('./includes/hook/index-start_include_module.php');
# HOOK;

if (!file_exists('./includes/' . $do . '.php')) {
$out .= _l('page_not_found');
$lefttitle = _l("Page Not Found");
} else {
include('./includes/' . $do . '.php');
}

# HOOK: User not found
@include('./includes/hook/index-end_include_module.php');
# HOOK;

$title = $program_name . ' - ' . $lefttitle;
$navbits = array($script_name => $program_name, '' => $lefttitle);

// Close all MSSQL connections, they are not needed after this point tbh
if (isset($gamecp_dbconnect)) {
@mssql_close($gamecp_dbconnect);
}
if (isset($items_dbconnect)) {
@mssql_close($items_dbconnect);
}
if (isset($donate_dbconnect)) {
@mssql_close($donate_dbconnect);
}
if (isset($user_dbconnect)) {
@mssql_close($user_dbconnect);
}
if (isset($data_dbconnect)) {
@mssql_close($data_dbconnect);
}
if (isset($billing_dbconnect)){
@mssql_close($billing_dbconnect);
}
}

# HOOK: User not found
@include('./includes/hook/index-start_out_var_output.php');
# HOOK;

# Draw the end of this script
gamecp_nav($isuser); // From phpBB 2.x
eval('print_outputs("' . gamecp_template('gamecp') . '");');

# HOOK: User not found
@include('./includes/hook/index-end_out_var_output.php');
# HOOK;
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