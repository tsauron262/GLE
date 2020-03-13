<?php

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';




$siret = GETPOST("siren");// "403554181";//"320387483";  
$mode = GETPOST("mode");
$siren = substr($siret, 0,9);




$xml_data = file_get_contents('request.xml');

$link = 'https://www.creditsafe.fr/getdata/service/CSFRServices.asmx';


$debug = 0;


	$sClient = new SoapClient($link."?wsdl", array('trace' => 1));
	$returnData = $sClient->GetData(array("requestXmlStr" =>str_replace("SIREN", str_replace(" ", "", $siren), $xml_data)));
        

$returnData = htmlspecialchars_decode($returnData->GetDataResult);
$returnData = str_replace("&", "et", $returnData);
$returnData = str_replace(" < ", " ", $returnData);
$returnData = str_replace(" > ", " ", $returnData);
$returnData = str_replace("<.", ".", $returnData);

if($debug == 2){
    header("Content-type: text/xml");
    echo $returnData;
    
    die;
}

$result = simplexml_load_string($returnData);

if($debug == 1){
    if ($result === false) {
        echo "Erreur lors du chargement du XML\n";
        foreach(libxml_get_errors() as $error) {
            echo "\t", $error->message;
        }
    }
    echo "<pre>";
    
    
    print_r($result);
    
    echo('fin');
    
    
    die;
}


if(stripos($result->header->reportinformation->reporttype, "Error") !== false){
    //echo json_encode($result);
    
    
    echo json_encode (array("Erreur"=> "".$result->body->errors->errordetail->code));  
}
else{
    if($mode != "xml")
        echo getJsonReduit($result, $siret, $siren);
    else{
        header("Content-type: text/xml");
        print_r($returnData);die();
    }
}



function getJsonReduit($result, $siret, $siren){
    $summary = $result->body->company->summary;
    $base = $result->body->company->baseinformation;
    $branches = $base->branches->branch;
    
    
    
    
    
    $adress = "".$summary->postaladdress->address." ".$summary->postaladresse->additiontoaddress;
    
    $note = "";
    $limit = 0;
    foreach(array("", "2013") as $annee){
//        if($note != "")
//            $note .= "\n";
        $champ = "rating".$annee;
        if($summary->$champ > 0){
            $note = dol_print_date(dol_now()) .($annee == ''? '' : '(Methode '.$annee.')')." : ".$summary->$champ ."/100";
//            if($annee != "")
//                if($note != "")
//                    $note .= "[Note de ".$annee."]";
//                else
//                    $note .= "[Attention note de ".$annee."]";
            foreach(array("", "desc1", "desc2") as $champ2){
                $champT = $champ.$champ2;
                if(isset($summary->$champT))
                    $note .= " ". str_replace($summary->$champ, "", $summary->$champT);
            }
        }
        $champ2 = "creditlimit".$annee;
        if(isset($summary->$champ2))
            $limit = $summary->$champ2;
    }
    
    $tabCodeP = explode(" ", $summary->postaladdress->distributionline);
    $codeP = $tabCodeP[0];
    
    $ville = str_replace($tabCodeP[0]." ","",$summary->postaladdress->distributionline);
    
    $tel = $summary->telephone;
    $nom = $summary->companyname;
    
    
    
    
    
    foreach($branches as $branche){
        if($branche->companynumber == $siret || ($siret == $siren && stripos($branche->type, "Siège") !== false)){
            $adress = $branche->full_address->address;
            //$nom = $branche->full_address->name;
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
        "limit" => "".price(intval($limit)),
        "tradename" => "".$summary->tradename,
        "info" => ""."",
        "Capital" => "".str_replace(" Euros", "", $summary->sharecapital));
//    $return = $result;
    return json_encode($return);
}