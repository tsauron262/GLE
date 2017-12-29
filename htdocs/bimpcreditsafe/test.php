<?php

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';




$siret = GETPOST("siren");// "403554181";//"320387483";   
$siren = substr($siret, 0,9);




$xml_data = file_get_contents('request.xml');

$link = 'https://www.creditsafe.fr/getdata/service/CSFRServices.asmx/GetData';


$xml_data = "requestXmlStr=".str_replace("SIREN", str_replace(" ", "", $siren), $xml_data);


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
    $base = $result->xmlresponse->body->company->baseinformation;
    $branches = $base->branches->branch;
    
    
    
    
    
    $adress = "".$summary->postaladdress->address." ".$summary->postaladresse->additiontoaddress;
    
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
    $codeP = $tabCodeP[0];
    
    $ville = str_replace($tabCodeP[0]." ","",$summary->postaladdress->distributionline);
    
    $tel = $summary->telephone;
    $nom = $summary->companyname;
    
    
    
    
    
    foreach($branches as $branche){
        if($branche->companynumber == $siret || ($siret == $siren && stripos($branche->type, "Siège") !== false)){
            $adress = $branche->full_address->address;
            $nom = $branche->full_address->name;
            $codeP = $branche->postcode;
            $ville = $branche->municipality;
            $siret = $branche->companynumber;
        }
           
    }
     // echo json_encode($summary);die;


    $return = array("Nom" => "".$nom,
        "Tva" => "".$base->vatnumber,
        "Tel" => "".$tel,
        "Naf" => "".$summary->activitycode,
        "Note" => "".$note,
        "Adresse" => "".$adress,
        "CodeP" => "".$codeP,
        "Ville" => "".$ville,
        "Siret" => "".$siret,
        "Capital" => "".str_replace(" Euros", "", $summary->sharecapital));
    //$return = $result;
    echo json_encode($return);
}