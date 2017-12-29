<?php

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';




$siret = GETPOST("siren");// "403554181";//"320387483";   
$siren = substr($siret, 0,9);




$xml_data = file_get_contents('request.xml');

$link = 'https://www.creditsafe.fr/getdata/service/CSFRServices.asmx';





	$sClient = new SoapClient($link."?wsdl", array('trace' => 1));
	$returnData = $sClient->GetData(array("requestXmlStr" =>str_replace("SIREN", str_replace(" ", "", $siren), $xml_data)));
        
        
//	//AFFICHE LA RÉPONSE 
//	echo "<pre>"; print_r($returnData);
//	
//	//AFFICHE LA REQUËTE XML
//	echo "REQUEST:<br/>" . htmlentities(str_ireplace('><', ">\n<", $sClient->__getLastRequest())) . "<br/><br/><br/>";
//	
//	//AFFICHE LA RÉPONSE XML
//	echo "Response:<br/>" . htmlentities(str_ireplace('><', ">\n<", $sClient->__getLastResponse())) . "<br/>";
//        
//        die;
//        
//        
//        
//        
//
//$xml_data = "requestXmlStr=".str_replace("SIREN", str_replace(" ", "", $siren), $xml_data);
//
//
//$context = stream_context_create(array('http'=>array(
//    'method' => 'POST',
//    'content' => $xml_data
//)));
//$returnData = file_get_contents($link, false, $context);

$returnData = htmlspecialchars_decode($returnData->GetDataResult);

$result = simplexml_load_string($returnData);

if(stripos($result->header->reportinformation->reporttype, "Error") !== false){
    //echo json_encode($result);
    
    
    echo json_encode (array("Erreur"=> "".$result->body->errors->errordetail->code));  
}
else{

    $summary = $result->body->company->summary;
    $base = $result->body->company->baseinformation;
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