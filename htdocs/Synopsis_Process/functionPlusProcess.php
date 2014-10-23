<?php

function getSNChrono($idChrono, $source) {
    global $db;
    $key = 1011;
    $dest = "productCli";
    $ordre = 1;


    $return = array();
    $result = getElementElement($source, $dest, $idChrono, null, $ordre);
    if (count($result) > 0) {
        $chronoTab = array();
        foreach ($result as $chrono)
            $chronoTab[] = $chrono['d'];
        $req = "SELECT `value` FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `chrono_refid` IN (" . implode(",", $chronoTab) . ") AND `key_id` = " . $key;
        $sql = $db->query($req);
        while ($result = $db->fetch_object($sql))
            $return[] = $result->value;
    }

    return implode(" | ", $return);
}

function bouttonEtatSav($idChrono) {
    global $db, $user;
    $return = "";
    $chrono = new Chrono($db);
    $chrono->loadObject = false;
    $chrono->fetch($idChrono);
    $chrono->getValues();
    $idEtat = 1056;
    if ($chrono->propalid) {
        require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
        $propal = new Propal($db);
        $propal->fetch($chrono->propalid);
        $chrono->propal = $propal;
    }
//    print_r($propal);

    $form = new Form($db);

    if ($chrono->values[1059] == 3)
        $sms = "&sendSms=\"+confirm(\"Envoyer mail + sms ?\");";
    else
        $sms = "&sendSms=\"+confirm(\"Envoyer mail ?\");";


    if (/*$chrono->values[$idEtat] == 2 && */$chrono->propalid && $propal->statut == 1) {
        $return .= "<a class='butAction' href='request.php?id=" . $idChrono . "&actionEtat=devisOk'>Devis Accepté</a>";
        $return .= "<br/>";
        $return .= "<a class='butAction' href='request.php?id=" . $idChrono . "&actionEtat=devisKo'>Devis Refusé</a>";
    } 

    if (/*$chrono->values[$idEtat] == 2 && */$chrono->propalid && $chrono->values[$idEtat] == 6) {
        $return .= "<p class='titInfo'>Frais de gestion : </p><input type='text' id='frais' value='0'/> TTC";
        $return .= "<p class='titInfo'>Dispo sous : </p><input type='text' id='nbJours' value='0'/><p class='titInfo'>jours</p>";
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&frais=\"+$(\"#frais\").attr(\"value\")+\"&nbJours=\"+$(\"#nbJours\").attr(\"value\")+\"&actionEtat=revProp&ligne=0\"'>Fermé</a>";
        $return .= "<br/>";
        $return .= "<a class='butAction' href='request.php?id=" . $idChrono . "&actionEtat=revProp&ligne=1'>Réviser devis</a>";
        $return .= "<br/>";
    } 
    
    if (/*$chrono->values[$idEtat] == 2 && */!$chrono->propalid) {
        $return .= '<a class="butAction" href="?id=' . $idChrono . '&action=createPC">Créer devis</a>';
    }
    
    if ($chrono->values[$idEtat] == 3 && $propal->statut > 0) {
        $return .= '<a class="butAction" href="request.php?id=' . $idChrono . '&actionEtat=repEnCours">Réparation en cours</a><br/>';
    }

    if ($chrono->values[$idEtat] == 1) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=pieceOk" . $sms . "'>Pièce reçue</a>";
        $return .= "<br/>";
    }

    if ($chrono->values[$idEtat] == 0) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=debDiago" . $sms . "'>Commencer diagnostic</a><br/>";
    }

    if ($chrono->propalid && $propal->statut == 0) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=attenteClient1" . $sms . "'>Envoyer Devis</a>";
        $return .= "</br>";
//        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=attenteClient2" . $sms . "'>Devis Garantie</a>";
    }

    if ($chrono->propalid && $propal->statut > 1 && ($chrono->values[$idEtat] == 4)) {
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&nbJours=\"+$(\"#nbJours\").attr(\"value\")+\"&actionEtat=repOk" . $sms . "'>Terminé</a>";
        $return .= "<p class='titInfo'>Dispo sous : </p><input type='text' id='nbJours' value='0'/><p class='titInfo'>jours</p>";
    }

    if ($chrono->values[$idEtat] == 9) {
        ob_start();
        $return .= $form->select_types_paiements("SAV");
        $return .= ob_get_clean();
        $return .= "</br>";
        $return .= "<a class='butAction' onclick='window.location = \"request.php?id=" . $idChrono . "&actionEtat=restituer&modeP=\"+$(\"#selectpaiementtype\").attr(\"value\");' >Restitué (Payer)</a>";
    }

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
            $return .= '<a href="#" onclick="dispatchePopObject(\'&msg=' . urlencode($message[2]) . '&fromsms=' . $fromsms . '&to=' . $to . '\', \'sms\',function(){}, \'SMS\', 100);">' . (($message[0] != "") ? img_picto($message[1], $message[0] . "@Synopsis_Tools") : $message[1]) . '</a> ';
        return $return;
    }
}
