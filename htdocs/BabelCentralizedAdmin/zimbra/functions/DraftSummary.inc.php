<?php
/*
 * BIMP-ERP by Synopsis et DRSI
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

class DraftSummary extends ZimbraSoapClient
{
    /** */
    var $intConversations = 0;

    /** */
    var $intMessages = 0;

    /** */
    function getDrafts()
    {
        // get context
        $objContext = $this->_getContext();

        // set headers
        $this->addHeaders($objContext);

        // build args
        $arrArgs = array();
        $arrArgs[] = new SoapParam('is:draft', 'query');

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
        return true;
    }

}
?>
