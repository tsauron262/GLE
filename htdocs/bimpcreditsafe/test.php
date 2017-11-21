<?php

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';




$siret = GETPOST("siren");// "403554181";//"320387483";   




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

    foreach(array("", "2013") as $annee){
        if($note != "")
            $note .= "\n";
        $champ = "rating".$annee;
        if($summary->$champ > 0){
            if($annee != "")
                $note .= "[Attention note de ".$annee."]";
            foreach(array("", "desc1", "desc2") as $champ2){
                $champT = $champ.$champ2;
                if(isset($summary->$champT))
                    $note .= " ". $summary->$champT;
            }
        }
    }
    
    $tabCodeP = explode(" ", $summary->postaladdress->distributionline);

     // echo json_encode($summary);die;


    $return = array("Nom" => "".$summary->companyname,
        "Tva" => "".$summary->safenumber,
        "Tel" => "".$summary->telephone,
        "Naf" => "".$summary->activitycode,
        "Note" => $note,
        "Adresse" => "".$summary->postaladdress->address." ".$summary->postaladresse->additiontoaddress,
        "CodeP" => $tabCodeP[0],
        "Ville" => str_replace($tabCodeP[0]." ","",$summary->postaladdress->distributionline),
        "Capital" => "".str_replace(" Euros", "", $summary->sharecapital));
    echo json_encode($return);
}
