<?php

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';




$siret = GETPOST("siren");// "403 554 181";//"320387483";   




$xml_data = file_get_contents('request.xml');

$link = 'https://www.creditsafe.fr/getdata/service/CSFRServices.asmx/GetData';


$xml_data = "requestXmlStr=".str_replace("SIREN", str_replace(" ", "", $siret), $xml_data);


$context = stream_context_create(array('http'=>array(
    'method' => 'POST',
    'content' => $xml_data
)));
$returnData = file_get_contents($link, false, $context);

$returnData = htmlspecialchars_decode($returnData);

$result = simplexml_load_string($returnData);

if(stripos($result->xmlresponse->header->reportinformation->reporttype, "Error") !== false){
    //echo json_encode($result);
    
    
    echo json_encode (array("Erreur"=> "".$result->xmlresponse->body->errors->errordetail->code));  
}
else{

    $summary = $result->xmlresponse->body->company->summary;


    if($summary->rating == "" || $summary->rating < 1){
        if($summary->rating2013 > 0)
            $note = $summary->rating2013 ." (attention note de 2013)";
        else
            $note = " aucune note trouvée.";
    }
    else
        $note = intval ($summary->rating);



    $return = array("Nom" => "".$summary->companyname,
        "Note" => $note,
        "Adresse" => $summary->postaladdress->address."\n".$summary->postaladdress->distributionline,
        "Capital" => "".str_replace(" Euros", "", $summary->sharecapital));
    echo json_encode($return);
}
