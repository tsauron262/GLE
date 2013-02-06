<?php
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */
include_once('../functions/InboxPreview.inc.php');

// =============================================================================
// Test
// =============================================================================
$objClient = new InboxPreview();
if (!$objClient->login('eos@synopsis-erp.com', 'redalert'))
{
    die('Authenticaton Failed');
}
else
{
    echo 'Authentication successful<br />';
}
$objClient->getNewMailPreview();

echo 'Conversations: ' . $objClient->intConversations . '<br />';
echo 'Messages: ' . $objClient->intMessages . '<br />';
echo '<hr />';
foreach ($objClient->arrConversations as $objCurrent)
{
    echo 'Conversation: ' . $objCurrent->strSubject . '<br />';
    foreach ($objCurrent->arrMessages as $objCurrentMsg)
    {
        echo 'Sender: ' . $objCurrentMsg->strSender. '<br />';
        echo 'Fragment: ' . $objCurrentMsg->strFragment. '<br />';
    }
    echo '<hr />';
}
?>
