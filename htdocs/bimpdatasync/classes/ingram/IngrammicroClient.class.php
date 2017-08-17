<?php

/**
 * @author <mig1098@hotmail.com>
 * @date 05-05-2016
 * */

namespace Library\Ingrammicro;

//
class IngrammicroClient
{

    private $url, $login, $password;
    private $queryparams = array();

    /**
     * @var boolean
     * true to get response in an object.
     * */
    private $getObject = false;

//
    public function __construct($url = '', $login = '', $password = '')
    {
        $this->url = $url;
        $this->login = $login;
        $this->password = $password;
    }

    public function getObject($bool = true)
    {
        $this->getObject = $bool;
        return $this;
    }

    public function call($method, $action, $data)
    {
        $url = $this->buildUrl();
        $body = $this->xmlParse($data, $action);
        $header = in_array($method, array('POST', 'PUT')) ? $this->buildHeader($body) : array();
//return $body;
        return ($this->getObject) ? $this->XMLDecode($this->curlRequest($url, $header, $body)) : $this->curlRequest($url, $header, $body);
    }

    public function curlRequest($url, $header, $body = null)
    {
        $ch = curl_init();
        $this->curlSetOptions($ch, $url, $header, $body);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($errno)
            throw new \Exception($error, $errno);
        return $response;
    }

    public function curlSetOptions($ch, $url, $header, $body)
    {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//if(!empty($header)){
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
//}
    }

    private function buildUrl()
    {
        return $this->url;
    }

    private function buildHeader($data)
    {
        return array(
            'Content-Action' => 'Ingram_Micro',
            'Content-Length' => strlen($data),
            'Content-Type'   => 'text/xml charset=utf-8'
        );
    }

    private function XMLDecode($string)
    {
        $xml = new \SimpleXMLElement($string);
        return $xml;
    }

    private function XMLEncode($data)
    {
        
    }
    private function xmlParse($content, $action)
    {
        $data = '';
        switch ($action) {
            case 'product':
            case 'ProductRequest':
                $data = is_array($content) ? $this->buildProduct($content) : $this->setCredentials($content);
                break;
            case 'SynchronousOrderRequestTransaction':
                $data = is_array($content) ? $this->buildOrderCreate($content) : $this->setCredentials($content);
                break;
            case 'order':
            case 'orderStatusTransaction':
            case 'orderDetailTransaction':
            case 'BaseRateTransaction':
            case 'OrderTrackingTransaction':
            case 'RMASubmittalRequestTransaction':
                $data = is_array($content) ? $this->buildOrder($content) : $this->setCredentials($content);
                break;
        }
        return $data;
    }

    private function setCredentials($content)
    {
        return str_replace(array('ingram_login', 'ingram_password'), array($this->login, $this->password), $content);
    }

    private function buildProduct($content)
    {//parse array to xml text
        return $content;
    }

    private function buildOrderCreate($content)
    {//parse array to xml text
        return $content;
    }

    private function buildOrder($content)
    {//parse array to xml text
        return $content;
    }
}
