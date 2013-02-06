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
include_once('./ZimbraSoapClient.inc.php');

class AccountStruct
{
    public $by;
    public $value;
}

class ChangePassword extends ZimbraSoapClient
{
    /** */
    function getChangePassword($strUsername, $strOldPassword, $strNewPassword)
    {
        // get context
        $objContext = $this->_getContext();

        // set headers
        $this->addHeaders($objContext);

        $arrArgs = array();
        $arrArgs[] = new SoapVar('<account by="name">' . $strUsername . '</account>', XSD_ANYXML);
        $arrArgs[] = new SoapParam($strOldPassword, 'oldPassword');
        $arrArgs[] = new SoapParam($strNewPassword, 'password');

        // call function
        if (!$this->execute('ChangePasswordRequest', $arrArgs, 'urn:zimbraAccount'))
        {
            return false;
        }
        return true;
    }

}
?>
