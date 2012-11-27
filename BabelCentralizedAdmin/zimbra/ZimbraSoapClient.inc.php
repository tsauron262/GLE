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
include_once('../datatypes/Context.inc.php');

class ZimbraSoapClient
{
    /** */
    var $soapClient = null;
    /** */
    var $strSOAPURL = '';
    /** */
    var $soapHeader = null;
    /** */
    var $strZimbraAuthToken = null;
    /** */
    var $strZimbraSessID = null;
    /** */
    var $objLastRequest = null;
    /** */
    var $objLastResponse = null;
    /** */
    var $objLastResponseRaw = null;

    /** Default Constructor */
    function ZimbraSoapClient()
    {
        if (defined('ZIMBRA_ADDRESS'))
        {
            $this->strSOAPURL = 'http://' . constant('ZIMBRA_ADDRESS') . '/service/soap/';
        }
        else
        {
            $this->strSOAPURL = 'http://10.91.130.61/service/soap/';
        }
    }

    /** */
    function addHeaders($objContext = null, $strURI = 'urn:zimbra')
    {
        if ($objContext)
        {
            $objContext = new Context();
        }

        $this->soapHeader = new SoapHeader($strURI,
                                     'context',
                                     $objContext);
    }

    /** */
    function execute($strFunctionName, $arrArgs, $strURI = 'urn:zimbraAdmin')
    {
        try
        {
            $this->soapClient = new SoapClient(null, array('location' => $this->strSOAPURL,
                                                     'uri'      => $strURI,
                                                     'trace'    => 1,
                                                     'exception'=> 1,
                                                     'soap_version'  => SOAP_1_1,
                                                     'style'    => SOAP_RPC,
                                                     'use'      => SOAP_LITERAL
                                                     ));

            // check the headers are set
            if ($this->soapHeader)
            {
                // add default headers (will not work in most cases)
                $this->addHeaders();
            }

            $this->soapClient->__setSoapHeaders($this->soapHeader);
            $this->soapClient->__soapCall($strFunctionName,
                                          $arrArgs,
                                          array('uri' => $strURI)
                                         );
        }
        catch (SoapFault $soapFault)
        {
            print ('SOAP Last Request:' . $this->soapClient->__getLastRequest());
            print ('SOAP Error:' . $soapFault);
            return false;
        }
        $this->objLastRequest = $this->soapClient->__getLastRequest();
        $this->objLastResponseRaw = $this->soapClient->__getLastResponse();
        $this->objLastResponse = simplexml_load_string($this->_getBodyContent($this->objLastResponseRaw));

        $this->soapClient = null;
        return true;
    }

    /** */
    function doRequest($strRequest)
    {
        try
        {
            $this->soapClient = new SoapClient(null, array('location' => $this->strSOAPURL,
                                                     'trace'    => 1,
                                                     'exception'=> 1,
                                                     'soap_version'  => SOAP_1_1,
                                                     'style'    => SOAP_RPC,
                                                     'use'      => SOAP_LITERAL
                                                     ));

            $strResponse = $this->soapClient->__doRequest($strRequest,
                                            $this->strSOAPURL,
                                            null,
                                            SOAP_1_1);
        }
        catch (SoapFault $soapFault)
        {
            MessageHandler::addWarning('SOAP Last Request: ' . str_replace('>', ">\n", print_r($this->soapClient->__getLastRequest(), true)));
            MessageHandler::addWarning(print_r($soapFault, true));
            return false;
        }
        $this->objLastRequest = $this->soapClient->__getLastRequest();
        $this->objLastResponseRaw = $this->soapClient->__getLastResponse();
        $this->objLastResponse = simplexml_load_string($this->_getBodyContent($this->objLastResponseRaw));

        $this->soapClient = null;
        return true;
    }

    /** */
    function login($strUsername, $strPassword)
    {
        // reset headers to blank
        $this->addHeaders(new Context(), 'urn:zimbra');

        // build args
        $arrArgs = array();
        $arrArgs[] = new SoapParam($strUsername, 'account');
        $arrArgs[] = new SoapParam($strPassword, 'password');
        if (!$this->execute('AuthRequest', $arrArgs, 'urn:zimbraAccount'))
        {
            return false;
        }
        // setup class params ready for more action
        $this->strZimbraAuthToken   = (string) $this->objLastResponse->authToken;
        $this->strZimbraSessID      = (string) $this->objLastResponse->sessionId;

        if (defined('ZIMBRA_DOMAIN')
            && !headers_sent())
        {
            setcookie('ls_last_username', $strUsername, 0, '/', constant('ZIMBRA_DOMAIN'));
            setcookie('ls_last_server', constant('ZIMBRA_ADDRESS'), 0, '/', constant('ZIMBRA_DOMAIN'));
            setcookie('ZM_AUTH_TOKEN', $this->strZimbraAuthToken, 0, '/', constant('ZIMBRA_DOMAIN'));
        }
        return true;
    }

    /** */
    function _getContext()
    {
        $objReturn = new Context();
        $objReturn->sessionId   = $this->strZimbraSessID;
        $objReturn->authToken   = $this->strZimbraAuthToken;

        return $objReturn;
    }

    /** */
    function _getBodyContent($strInput)
    {
        $strReturn = $strInput;
        $intBodyStart = strpos($strReturn, '<soap:Body>') + strlen('<soap:Body>');
        $intBodyEnd = strpos($strReturn, '</soap:Body>');

        $strReturn = substr($strReturn, $intBodyStart, $intBodyEnd - $intBodyStart);
        return $strReturn;
    }

    /** */
    function getInfoRequest()
    {
        // get context
        $objContext = $this->_getContext();

        // set headers
        $this->addHeaders($objContext);

        // build args
        $arrArgs = array();

        // call function
        if (!$this->execute('GetInfoRequest', $arrArgs, 'urn:zimbraAccount'))
        {
            return false;
        }

        return true;
    }
}
?>
