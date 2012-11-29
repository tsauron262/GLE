<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 29 avr. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : gisgraphy.php
  * GLE-1.1
  */
require_once('pre.inc.php');
$url = "http://services.gisgraphy.com/fulltext/fulltextsearch";
$q = urlencode('Aix en Provence');
if($_REQUEST['socid'] > 0)
{
    $soc = new Societe($db);
    $soc->fetch($_REQUEST['socid']);
    $q = urlencode($soc->cp. " ".$soc->ville);
}
//print $soc->ville;
$param = 'q='.$q."&placetype=city&country=FR&lang=fr&format=XML&style=FULL&indent=true&radius=1000";

//print $url."?".$param;

$_curl = curl_init();
curl_setopt($_curl, CURLOPT_URL,$url."?".$param);
curl_setopt($_curl, CURLOPT_POST,           false);
curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($_curl, CURLOPT_VERBOSE, true); // Display communication with server
curl_setopt($_curl, CURLOPT_TIMEOUT, 5);

$response = curl_exec($_curl);
if ($response)
{
    $xml = new DOMDocument();
//    var_dump($response);
    $xml->loadXML($response);
    $lat;$long;$googleUrl;
    foreach($xml->getElementsByTagName('double') as $key =>$val)
    {
        if ($val->getAttribute('name')=="lat")
        {
            $lat = $val->nodeValue;
        }
        if ($val->getAttribute('name')=="lng")
        {
            $long = $val->nodeValue;
        }
    }
    foreach($xml->getElementsByTagName('str') as $key =>$val)
    {
        if ($val->getAttribute('name')=="google_map_url")
        {
            $googleUrl = $val->nodeValue;
        }
    }

    $url = "http://services.gisgraphy.com/street/streetsearch";
    $q = urlencode('bras d\'or');
    if($_REQUEST['socid'] > 0)
    {
        $q = urlencode(preg_replace('/^[0-9 ]*/','',$soc->adresse));
    }
    $param = 'name='.$q."&lang=fr&format=XML&lat=".$lat."&lng=".$long;
//print $param;
//name=rue+de+Brest&lang=fr&format=XML&lat=48.516666412353516&lng=-2.7833333015441895

    $_curl = curl_init();
    curl_setopt($_curl, CURLOPT_URL,$url."?".$param);
    curl_setopt($_curl, CURLOPT_POST,           false);
    curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($_curl, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($_curl);
    $xml = new DOMDocument();
//    var_dump($response);
    //$response = substr_replace($response,'',0,56);
//    print $response;

     $xml->loadXML($response);

//    var_dump($xml->getMessage());
    $xmlStr='<ajax-response>';
    $items = $xml->getElementsByTagName('numFound');


    if ($items->item(0)->nodeValue == 0)
    {
        $xmlStr .= "<result>";
        $xmlStr .= '<countryCode>FR</countryCode>';
        $xmlStr .= '<name>'.$soc->nom.'</name>';
        $xmlStr .= '<lat>'.$lat.'</lat>';
        $xmlStr .= '<lng>'.$long.'</lng>';
        $xmlStr .= '<googleUrl>'.$googleUrl."</googleUrl>";
        $xmlStr .= "</result>";
    } else {
        $xmlStr .= "<result>";
        $xmlStr .= '<countryCode>FR</countryCode>';
        $xmlStr .= '<name>'.$soc->nom.' ('.$xml->getElementsByTagName('name')->item(0)->nodeValue.')</name>';
        $xmlStr .= '<lat>'.$xml->getElementsByTagName('lat')->item(0)->nodeValue.'</lat>';
        $xmlStr .= '<lng>'.$xml->getElementsByTagName('lng')->item(0)->nodeValue.'</lng>';
        $xmlStr .= '<googleUrl>'.$googleUrl."</googleUrl>";
        $xmlStr .= "</result>";
    }

} else {
    $response = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>' . "<error>Error Curl</error>";
}

    $xmlStr.='</ajax-response>';
   header("Content-Type: text/xml");
    //$xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    //$xmlStr .= $response;
    print $xmlStr;



?>
