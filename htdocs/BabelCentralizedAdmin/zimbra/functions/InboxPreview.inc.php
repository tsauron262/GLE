<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */

include_once('../ZimbraSoapClient.inc.php');
include_once('../datatypes/Conversation.inc.php');
include_once('../datatypes/Message.inc.php');

class InboxPreview extends ZimbraSoapClient
{
    /** */
    var $intConversations = 0;

    /** */
    var $intMessages = 0;

    /** */
    var $arrConversations = null;

    /** */
    function getNewMailPreview()
    {
        // get context
        $objContext = $this->_getContext();

        // set headers
        $this->addHeaders($objContext);

        // build args
        $arrArgs = array();
        $arrArgs[] = new SoapParam('is:unread', 'query');

        // call function
        if (!$this->execute('SearchRequest', $arrArgs, 'urn:zimbraMail'))
        {
            return false;
        }

        $this->intConversations = count($this->objLastResponse->c);
        $intTotalNew = 0;
        foreach ($this->objLastResponse->c as $objCurrent)
        {
                $intTotalNew += (int) count($objCurrent->m);
        }
        $this->intMessages = $intTotalNew;

        // parse out the messages
        foreach ($this->objLastResponse->c as $objCurrent)
        {
            $objNewConversation = new Conversation();
            $arrAttrib = $objCurrent->attributes();
            $objNewConversation->intConversationID = (int) $arrAttrib['id'];
            $objNewConversation->strSubject = (string) $objCurrent->su;

            $intMessageNo = 0;
            foreach ($objCurrent->m as $objMessage)
            {
                $objNewMessage = new Message();
                $objNewMessage->intConversationID = $objNewConversation->intConversationID;
                $arrMsgAttrib = $objMessage->attributes();
                $objNewMessage->intMessageID = (int) $arrMsgAttrib['id'];
                $objNewMessage->strSubject = (string) $objCurrent->su;
                $objNewMessage->strFragment = (string) $objCurrent->fr;
                $arrSenderAttrib = $objCurrent->e[$intMessageNo]->attributes();
                $objNewMessage->strSender = (string) $arrSenderAttrib['a'];

                $objNewConversation->arrMessages[] = $objNewMessage;
                $intMessageNo++;
            }

            $objNewConversation->strFragment = (string) $objCurrent->fr;
            $this->arrConversations[] = $objNewConversation;
        }

        return true;
    }

}
?>
