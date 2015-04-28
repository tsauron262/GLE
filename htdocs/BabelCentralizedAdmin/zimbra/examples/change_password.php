<?php
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */

include_once('../functions/ChangePassword.inc.php');

// =============================================================================
// Test
// =============================================================================
$objClient = new ChangePassword();
if (!$objClient->login('admin@mworks.com.my', 'abc123'))
{
    die('Authenticaton Failed');
}
else
{
    echo 'Authentication successful<br />';
}
$objClient->getChangePassword('corsc@mworks.com.my', 'abc123', 'abc123');

echo nl2br(print_r($objClient, true));
?>
