<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : gmaps-xmlresponse.php
  * GLE-1.1
  */


    require_once('../main.inc.php');

    $url = 'http://maps.google.com/maps/api/geocode/xml';

    $socId=$_REQUEST['socid'];
    $id = $_REQUEST['id'];

    if ($id > 0)
    {
        $requete = "SELECT * FROM Babel_GeoBI WHERE id = ".$id;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $xmlStr = "<ajax-response>";

        $xmlStr .= "<result>";
        $xmlStr .= '<countryCode>'.$res->codeCountry.'</countryCode>';
        $xmlStr .= '<name>'.utf8_encode($res->label).'</name>';
        $xmlStr .= '<lat>'.$res->lat.'</lat>';
        $xmlStr .= '<lng>'.$res->lng.'</lng>';
        $xmlStr .= '<googleUrl>rzqr</googleUrl>';
        $xmlStr .= "</result>";

        $xmlStr.='</ajax-response>';
       header("Content-Type: text/xml");
       //$xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' . $xmlStr;
        //$xmlStr .= $response;
        print $xmlStr;


    } else {
        $soc = new Societe($db);
        $soc->fetch($socId);
        $add = urlencode($soc->adresse_full .",".$soc->pays);
        $param = '?address='.$add.'&sensor=true';
    //print $url.$param;
        $_curl = curl_init();
        curl_setopt($_curl, CURLOPT_URL,$url.$param);
        curl_setopt($_curl, CURLOPT_POST,           false);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($_curl, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($_curl);
        $xml = new DOMDocument('1.0', 'utf-8');
    //    var_dump($response);
        //$response = substr_replace($response,'',0,56);
    //    print $response;
        //print $xml->getElementsByTagName('GeocodeResponse')->item(0)->length;
        $xml->loadXML($response);
        while ($iter < 3)
       {

        if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS'
            && $xml->getElementsByTagName('status')->item(0)->nodeValue!='INVALID_REQUEST'
            && !$xml->getElementsByTagName('result')->item(0)
            && $xml->getElementsByTagName('GeocodeResponse')->length > 0)
        {
            //var_dump($xml->getElementsByTagName('result')->item(0));
            sleep(3);
            $response = curl_exec($_curl);
            $xml->loadXML($response);
        }
        $iter ++;
    }
    if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS')
    {
        $add = urlencode($soc->ville .",".$soc->pays);
        $param = '?address='.$add.'&sensor=true';
    //print $url.$param;
        $_curl = curl_init();
        curl_setopt($_curl, CURLOPT_URL,$url.$param);
        curl_setopt($_curl, CURLOPT_POST,           false);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($_curl, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($_curl);
        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->loadXML($response);
        $iter=0;
        while ($iter < 3)
       {

        if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS'
            && $xml->getElementsByTagName('status')->item(0)->nodeValue!='INVALID_REQUEST'
            && !$xml->getElementsByTagName('result')->item(0)
            && $xml->getElementsByTagName('GeocodeResponse')->length > 0)
        {
            //var_dump($xml->getElementsByTagName('result')->item(0));
            sleep(3);
            $response = curl_exec($_curl);
            $xml->loadXML($response);
        }
        $iter ++;

        }
    }

         //var_dump($xml);

        $xmlStr='<ajax-response>';
        if ($xml->getElementsByTagName('status')->item(0)->nodeValue!='ZERO_RESULTS' && $xml->getElementsByTagName('status')->item(0)->nodeValue!='INVALID_REQUEST' && $xml->getElementsByTagName('result')->length>0)
        {
            $locNode = $xml->getElementsByTagName('result')->item(0)->getElementsByTagName('location')->item(0);
            $lat = $locNode->getElementsByTagName('lat')->item(0)->nodeValue;
            $lng = $locNode->getElementsByTagName('lng')->item(0)->nodeValue;
            $codeCountry='FRANCE';
            foreach( $xml->getElementsByTagName('result')->item(0)->getElementsByTagName('address_component') as $key=>$val)
            {
                if ($val->getElementsByTagName('type')->item(0)->nodeValue == 'country' || $val->getElementsByTagName('type')->item(1)->nodeValue == 'country')
                {
                    $codeCountry = $val->getElementsByTagName('short_name')->item(0)->nodeValue;
                }
            }
            $xmlStr .= "<result>";
            $xmlStr .= '<countryCode>'.$codeCountry.'</countryCode>';
            $xmlStr .= '<name>'.utf8_encode($soc->nom).'</name>';
            $xmlStr .= '<lat>'.$lat.'</lat>';
            $xmlStr .= '<lng>'.$lng.'</lng>';
            $xmlStr .= '<googleUrl></googleUrl>';
            $xmlStr .= "</result>";

            //if option save pour import global
            if ($_REQUEST['option']=="save")
            {
                $requete = "INSERT INTO `Babel_GeoBI` (`lat`,`lng`,`socid`,`countryCode`,`label`)
                                 VALUES
                                        ('".$lat."','".$lng."',".$soc->id.", '".$codeCountry."', '".$soc->name."')";
                $sql =$db->query($requete);
            }


        } else {
            // nous voulons un joli affichage
            $xml->formatOutput = true;
            $GeocodeResponse = $xml->getElementsByTagName('GeocodeResponse')->item(0);
            //if ($GeocodeResponse)
                $xmlStr .= $xml->saveXML($GeocodeResponse);
        }

        $xmlStr.='</ajax-response>';
       header("Content-Type: text/xml");
       //$xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' . $xmlStr;
        //$xmlStr .= $response;
        print $xmlStr;

    }




?>
