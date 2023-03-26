<?php
/**
 * Game Control Panel v2
 * Copyright (c) www.intrepid-web.net
 *
 * The use of this product is subject to a license agreement
 * which can be found at http://www.intrepid-web.net/rf-game-cp-v2/license-agreement/
 */
define('ODP_RECHECK_7Z_ENCRYPT75469009373', true);

# Set main variables
$notuser = true;
$isuser = false;

# Include main files
# We are doing this _after_ so that we dont show up as logged in!
include('./gamecp_common.php');

logoutUser();

# Main variables, set after the fact
$lefttitle = 'Logout';
$title = $program_name . ' - ' . $lefttitle;

# Display message
#$out .= '<div class="alert alert-warning"><h4>Notice</h4><p>You Have Successfully Logged Out !</p></div>';
header("Refresh: 3; URL=/index.php");
$out .= '<p style="text-align: center;">Successfully Log Out. Now you will Redirecting..</p>';

# Write the rest of the page
gamecp_nav(); // From phpBB 2.x
//$navbits = construct_navbits($navbits);
//eval('$navbar = "' . fetch_template('navbar') . '";');
//eval('print_output("' . fetch_template('GameCP') . '");');
eval('print_outputs("' . gamecp_template('gamecp') . '");');
?>