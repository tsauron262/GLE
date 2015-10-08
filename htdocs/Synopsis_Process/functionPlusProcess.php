<?php

function getSNChrono($idChrono, $source) {
    global $db;
    $key = array("" => 1011, "Mdp : " => 1057, "Login : " => 1063);
    $keyI = array();
    foreach($key as $un => $deux)
        $keyI[$deux] =  $un;
    $dest = "productCli";
    $ordre = 1;

    $returnStr = "";
    $result = getElementElement($source, $dest, $idChrono, null, $ordre);
    if (count($result) > 0) {
        $return1 = array();
        $chronoTab = array();
        foreach ($result as $chrono) 
            $chronoTab[] = $chrono['d'];
        $result2 = getElementElement($source, $dest, null, $chronoTab);
        $chrono2 = new Chrono($db);
        foreach($result2 as $ligne2){
            if((is_array($idChrono) && !in_array($ligne2['s'],$idChrono)) || (!is_array($idChrono) && $idChrono != $ligne2['s'])){
                $chrono2->fetch($ligne2['s']);
                $returnStr .= $chrono2->getNomUrl(1)."</br>";
            }
        }
        
//        $req = "SELECT `value`, key_id  FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `chrono_refid` IN (" . implode(",", $chronoTab) . ") AND `key_id` IN (" . implode(",", $key) . ") Order BY chrono_refid";
        $req = "SELECT *  FROM `" . MAIN_DB_PREFIX . "synopsischrono_chrono_101` WHERE `id` IN (" . implode(",", $chronoTab) . ")";
        $sql = $db->query($req);
        while ($result = $db->fetch_object($sql)){
//            $returnStr .= $keyI[$result->key_id]." ".$result->value."\n";
            $returnStr .= $result->N__Serie."\n";
            $returnStr .= "Login : ".$result->Login_Admin."\n";
            $returnStr .= "Mdp : ".$result->Mdp_Admin."\n";
        }
    }

    return $returnStr;
}

function bouttonEtatSav($idChrono) {
    global $db, $user;
    $return = "";
    $chrono = new Chrono($db);
    $chrono->loadObject = false;
    $chrono->fetch($idChrono);
    $chrono->getValues();
    $etatSav = $chrono->values['Etat'];
    if ($chrono->propalid) {
//        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
//        $propal = new Propal($db);
//        $propal->fetch($chrono->propalid);
//        $chrono->propal = $propal;
        $sql = $db->query("SELECT fk_statut FROM " . MAIN_DB_PREFIX . "propal WHERE rowid = " . $chrono->propalid);
        if ($db->num_rows($sql) > 0) {
            $result = $db->fetch_object($sql);
            $propStatut = $result->fk_statut;
            $propId = $chrono->propalid;
        }
    }
//    print_r($propal);

    $form = new Form($db);

    if ($chrono->values[1059] == 3)
        $sms = "&sendSms=\"+confirm(\"Envoyer mail + sms ?\");";
    else
        $sms = "&sendSms=\"+confirm(\"Envoyer mail ?\");";


    if (/* $etatSav == 2 && */$propId && $propStatut == 1) {
        $return .= "<a class='butAction' href='request.php?id=" . $idChrono . "&actionEtat=devisOk'>Devis Accepté</a>";
        $return .= "<br/>";
        $return .= "<a class='butAction' href='request.php?id=" . $idChrono . "&actionEtat=devisKo'>Devis Refusé</a>";
    }

    if (/* $etatSav == 2 && */$propId && $etatSav == 6) {
        $return .= "<p class='titInfo'>Frais de gestion : </p><input type='text' id='frais' value='0'/> TTC";
        $return .= "<p class='titInfo'>Dispo sous : </p><input type='text' id='nbJours' value='0'/><p class='titInfo'>jours</p>";
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&frais=\"+$(\"#frais\").val()+\"&nbJours=\"+$(\"#nbJours\").val()+\"&actionEtat=revProp&ligne=0\"'>Fermé</a>";
        $return .= "<br/>";
        $return .= "<a class='butAction' href='request.php?id=" . $idChrono . "&actionEtat=revProp&ligne=1'>Réviser devis</a>";
        $return .= "<br/>";
    }

    if (/* $etatSav == 2 && */!$propId) {
        $return .= '<a class="butAction" href="?id=' . $idChrono . '&action=createPC">Créer devis</a>';
    }

    if ($etatSav == 3 && $propStatut > 0) {
        $return .= '<a class="butAction" href="request.php?id=' . $idChrono . '&actionEtat=repEnCours">Réparation en cours</a><br/>';
    }

    if ($etatSav == 1) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=pieceOk" . $sms . "'>Pièce reçue</a>";
        $return .= "<br/>";
    }

    if ($etatSav == 0) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=debDiago" . $sms . "'>Commencer diagnostic</a><br/>";
    }

    if ($propId && $propStatut == 0) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=attenteClient1" . $sms . "'>Envoyer Devis</a>";
        $return .= "</br>";
    }

    if ($propId && $propStatut > 1 && ($etatSav == 4)) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&nbJours=\"+$(\"#nbJours\").attr(\"value\")+\"&actionEtat=repOk" . $sms . "'>Terminé</a>";
        $return .= "<p class='titInfo'>Dispo sous : </p><input type='text' id='nbJours' value='0'/><p class='titInfo'>jours</p>";
    }

    if ($etatSav == 9) {
        ob_start();
        $return .= $form->select_types_paiements("SAV");
        $return .= ob_get_clean();
        $return .= "</br>";
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=restituer&modeP=\"+$(this).parent().find(\"#selectpaiementtype\").val();' >Restitué (Payer)</a>";
    }
    
    if($propStatut == 0 || $propStatut == 1){
        
        $return .= "<br/><a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=attenteClient2" . $sms . "'>Devis Garantie</a>";
    }
    
    $return .= "<a class='butCache' id='butCacheReMail'>Recontacter</a>";
    $return .= "<div class='panCache' id='panCacheReMail'><select id='mailType'>";
    foreach (array("Facture" => "Facture", "Devis" => "Devis", "debut" => "PC", "debDiago" => "Diagnostique", "commOk" => "Piéce commandé", "repOk" => "Réparation OK", "revPropFerm" => "Devis refusé", "pieceOk"=>"Piéce reçue") as $val => $nom)
        $return .= "<option value='".$val."'>".$nom."</option>";
    $return .= "</select><a class='butAction' style='display: inline;' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=mailSeul&mailType=\"+$(\"#mailType\").attr(\"value\")'>Réenvoyer</a>";

    return $return;
}

function pictoSMS($idChrono) {
    global $db;
    $chrono = new Chrono($db);
    $chrono->fetch($idChrono);
    if (is_object($chrono->contact) && $chrono->contact->phone_mobile != "")
        $to = $chrono->contact->phone_mobile;
    elseif (is_object($chrono->societe) && $chrono->societe->phone != "")
        $to = $chrono->societe->phone;
    $fromsms = urlencode('SAV BIMP');

    $to = str_replace(" ", "", $to);


    $tabMessage = array(array("camion", "MEss 1", "Message avec espace é à l'oin"),
        array("object_licence", "Mess 2", "Message 2"));

    if ($to == "" || stripos($to, "6") === false)
        return "Pas de numéro de mobile";
//    $to = urlencode("+33628335081");
    else {
        $return = "";
        foreach ($tabMessage as $message)
            $return .= '<a href="#" onclick="dispatchePopObject(\'&msg=' . urlencode($message[2]) . '&fromsms=' . $fromsms . '&to=' . $to . '\', \'sms\',function(){}, \'SMS\', 100);">' . (($message[0] != "") ? img_picto($message[1], $message[0] . "@synopsistools") : $message[1]) . '</a> ';
        return $return;
    }
}

function transfertA($chronoId){
   return "<input type='checkbox' class='hideTrans' value='on' id='mailTrans' name='mailTrans'/>";
}
