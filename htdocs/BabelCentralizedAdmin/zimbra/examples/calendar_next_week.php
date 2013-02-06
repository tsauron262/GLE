<?php
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */

include_once('../functions/CalendarNextWeek.inc.php');

// =============================================================================
// Test
// =============================================================================
$objClient = new CalendarNextWeek();
if (!$objClient->login('admin@mworks.com.my', 'abc123'))
{
    die('Authenticaton Failed');
}
else
{
    echo 'Authentication successful<br />';
}
$objClient->getCalendarNextWeek();

echo nl2br(print_r($objClient, true));
foreach ($objClient->arrConversations as $objCurrent)
{
    echo 'Conversation: ' . $objCurrent->strSubject . '<br />';
    echo 'Fragment: ' . $objCurrent->strFragment. '<br />';
    echo '<hr />';
}
?>
