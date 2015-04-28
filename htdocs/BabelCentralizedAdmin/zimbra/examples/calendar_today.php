<?php
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */

include_once('../functions/CalendarToday.inc.php');

// =============================================================================
// Test
// =============================================================================
$objClient = new CalendarToday();
if (!$objClient->login('admin@mworks.com.my', 'digital'))
{
    die('Authenticaton Failed');
}
else
{
    echo 'Authentication successful<br />';
}
$objClient->getCalendarToday();

echo nl2br(print_r($objClient, true));
foreach ($objClient->arrConversations as $objCurrent)
{
    echo 'Conversation: ' . $objCurrent->strSubject . '<br />';
    echo 'Fragment: ' . $objCurrent->strFragment. '<br />';
    echo '<hr />';
}

?>
