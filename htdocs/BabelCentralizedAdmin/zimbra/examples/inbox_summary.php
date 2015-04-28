<?php
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */

include_once('../functions/InboxSummary.inc.php');

// =============================================================================
// Test
// =============================================================================
define('ZIMBRA_ADDRESS', '10.91.130.61');
define('ZIMBRA_DOMAIN', 'synopsis-erp.com');

$objClient = new InboxSummary();
if (!$objClient->login('eos@synopsis-erp.com', 'redalert'))
{
    die('Authenticaton Failed');
}
else
{
    echo 'Authentication successful<br />';
}
$objClient->getNewMail();

//echo nl2br(print_r($objClient, true));
echo 'Conversations: ' . $objClient->intConversations . '<br />';
echo 'Messages: ' . $objClient->intMessages . '<br />';

?>
