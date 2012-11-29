<?php
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */

include_once('../functions/DraftSummary.inc.php');


// =============================================================================
// Test
// =============================================================================
$objClient = new DraftSummary();
if (!$objClient->login('admin@mworks.com.my', 'abc123'))
{
    die('Authenticaton Failed');
}
else
{
    echo 'Authentication successful<br />';
}
$objClient->getDrafts();

//echo nl2br(print_r($objClient, true));
echo 'Conversations: ' . $objClient->intConversations . '<br />';
echo 'Messages: ' . $objClient->intMessages . '<br />';
?>
