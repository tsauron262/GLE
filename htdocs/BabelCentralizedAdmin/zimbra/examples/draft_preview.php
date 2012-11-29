<?php
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */

include_once('../functions/DraftPreview.inc.php');
define ("ZIMBRA_ADDRESS","10.91.130.61");
// =============================================================================
// Test
// =============================================================================
$objClient = new DraftPreview();
//if (!$objClient->login('corsc@mworks.com.my', 'abc123'))
if (!$objClient->login('eos@synopsis-erp.com', 'redalert'))
{
    die('Authenticaton Failed');
}
else
{
    echo 'Authentication successful<br />';
}
$objClient->getDraftMailPreview();

//echo nl2br(print_r($objClient, true));
echo 'Conversations: ' . $objClient->intConversations . '<br />';
echo 'Messages: ' . $objClient->intMessages . '<br />';
echo '<hr />';
foreach ($objClient->arrConversations as $objCurrent)
{
    echo 'Conversation: ' . $objCurrent->strSubject . '<br />';
    echo 'Fragment: ' . $objCurrent->strFragment. '<br />';
    echo '<hr />';
}
// =============================================================================
// Debug output
// =============================================================================
MessageHandler::dumpMessages();
MessageHandler::dumpWarnings();
MessageHandler::dumpErrors();

?>
