<?php


if (isset($chrono->extraValue[$chrono->id]['Centre']['value']) && isset($tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']])) {
    $tel = $tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']][0];
    $fromMail = "SAV BIMP<" . $tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']][1] . ">";
    $nomCentre = $tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']][2];
    $lettreCentre = $chrono->extraValue[$chrono->id]['Centre']['value'];
    if ($lettreCentre == "GB")
        $lettreCentre = "GA";
    if ($lettreCentre == "M" || $lettreCentre == "AB")
        $lettreCentre = "A";
    if ($lettreCentre == "CFB")
        $lettreCentre = "CF";
    $sql = $db->query("SELECT * FROM  `" . MAIN_DB_PREFIX . "entrepot` WHERE  `label` LIKE  'SAV" . $lettreCentre . "'");
    if ($db->num_rows($sql) > 0) {
        $result = $db->fetch_object($sql);
        $idEntrepot = $result->rowid;
    }
} else {
    $tel = "N/C";
    $fromMail = "SAV BIMP<no-replay@bimp.fr>";
}

if (isset($_REQUEST['actionEtat'])) {
    $action = $_REQUEST['actionEtat'];

    if (isset($chrono->contact) && isset($chrono->contact->email) && $chrono->contact->email != '')
        $toMail = $chrono->contact->email;
    else
        $toMail = $chrono->societe->email;
    
    if ($action == "commandeOK" && $chrono->propal->id > 0) {// && $chrono->extraValue[$chrono->id]['Etat']['value'] != 1) {
        //Si commmande apple a 0 on passse la propal sous garenti.
        if ($chrono->extraValue[$chrono->id]['Etat']['value'] < 9) {
            if (isset($_REQUEST['prix']) && $_REQUEST['prix'] == 0 && is_object($chrono->propal)) {
                require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/partsCart.class.php");
                $part = new partsCart($db);
                $part->chronoId = $chrono->id;
                $part->loadCart();
                $part->addThisToPropal($chrono->propal);
                $action = "attenteClient2"; //Pour simuler click bouton Sous garentie
            }
            $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
            $chrono->note .= "Piéce commandée le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
            $chrono->update($chrono->id);

            $chrono->setDatas($chrono->id, array($idEtat => 1));
            $attentePiece = 1;

            if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms'])
                envoieMail("commOk", $chrono, null, $toMail, $fromMail, $tel, $nomMachine, $nomCentre);
        }
    }

    if ($action == "mailSeul" && isset($_REQUEST['mailType'])) {
        $_REQUEST['sendSms'] = true;
        envoieMail($_REQUEST['mailType'], $chrono, $obj, $toMail, $fromMail, $tel, $nomMachine, $nomCentre);
    }

    if ($action == "restPret" && isset($_REQUEST['pret'])) {
        $chronoPret = new Chrono($db);
        $chronoPret->fetch($_REQUEST['pret']);
        $chronoPret->setDatas($_REQUEST['pret'], array(1081 => 1));
    }
}

function testNumSms($to) {
    $to = str_replace(" ", "", $to);
    if ($to == "")
        return 0;
    if ((stripos($to, "06") === 0 || stripos($to, "07") === 0) && strlen($to) == 10)
        return 1;
    if ((stripos($to, "+336") === 0 || stripos($to, "+337") === 0) && strlen($to) == 12)
        return 1;
    return 0;
}

function sendSms($chrono, $text) {
    if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms']) {
        require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");
        if (is_object($chrono->contact) && testNumSms($chrono->contact->phone_mobile))
            $to = $chrono->contact->phone_mobile;
        elseif (is_object($chrono->contact) && testNumSms($chrono->contact->phone_pro))
            $to = $chrono->contact->phone_pro;
        elseif (is_object($chrono->contact) && testNumSms($chrono->contact->phone_perso))
            $to = $chrono->contact->phone_perso;
        elseif (is_object($chrono->societe) && testNumSms($chrono->societe->phone))
            $to = $chrono->societe->phone;
        
        $text.= " ".$chrono->ref;
        //$to = "0628335081";
        $fromsms = urlencode('SAV BIMP');

        $to = traiteNumMobile($to);
        if ($to == "" || (stripos($to, "+336") === false && stripos($to, "+337") === false))
            return 0;


//    echo $to . "   |   " . $text;
//    die;

        $smsfile = new CSMSFile($to, $fromsms, $text);
        $return = $smsfile->sendfile();


        if (!$return)
            $_SESSION['error']["SMS non envoyé"] = 1;
        else
            $_SESSION['error']["SMS envoyé"] = 0;

        return $return;
    }
}

function envoieMail($type, $chrono, $obj, $toMail, $fromMail, $tel, $nomMachine, $nomCentre) {
    global $db;
    $delai = (isset($_REQUEST['nbJours']) && $_REQUEST['nbJours'] > 0 ? "dans " . $_REQUEST['nbJours'] . " jours" : "dès maintenant");

//    $signature = '<div id="signature_Bimp"><div style="font-family: Arial, sans-serif; font-size: 13px;"><div style="margin: 0 0 8px 0;"><table border="0"><tbody><tr valign="middle"><td><a href="http://www.bimp.fr/" target="_blank"><img alt="" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/bimpcomputer.png"></a></td><td style="text-align: left;"><span style="font-size: large;"><span style="color: #181818;"><strong class="text-color theme-font">BIMP SAV</strong><span style="color: #181818;"> </span></span></span><br><div style="margin-bottom: 0px; margin-top: 8px;"><span style="font-size: medium;"><span style="color: #cb6c09;"></span></span></div><div style="margin-bottom: 0px; margin-top: 0px;"><span style="font-size: medium;"><span style="color: #808080;">Centre de Services Agrées Apple</span></span></div><div style="color: #828282; font: 13px Arial; margin-top: 10px; text-transform: none;"><a href="https://plus.google.com/+BimpFr/posts" style="text-decoration: underline;"><img alt="Google Plus Page" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/googlepluspage.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://twitter.com/BimpComputer" style="text-decoration: underline;"><img alt="Twitter" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/twitter.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://www.linkedin.com/company/bimp" style="text-decoration: underline;"><img alt="LinkedIn" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/linkedin.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="http://www.viadeo.com/fr/company/bimp" style="text-decoration: underline;"><img alt="Viadeo" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/viadeo.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a> <a href="https://www.facebook.com/bimpcomputer" style="text-decoration: underline;"><img alt="Facebook" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/facebook.png" style="padding: 0px 0px 5px 0px; vertical-align: middle;" height="16" border="0" width="16"></a><br></div></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td><a href="http://bit.ly/1MsmSB8"><img moz-do-not-send="true" src="http://www.bimp.fr/emailing/signatures/evenementiel2.png" alt=""></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td><img alt="" moz-do-not-send="true" src="http://bimp.fr/emailing/signatures/pictoarbre.png"></td><td style="text-align: left;"><span style="font-size: small;"><span style="color: #009933;"> Merci de n\'imprimer cet e-mail que si nécessaire</span></span></td></tr></tbody></table><table border="0"><tbody><tr valign="middle"><td style="text-align: justify;"><span style="font-size: small;"><span style="color: #888888;"><small>Ce message et éventuellement les pièces jointes, sont exclusivement transmis à l\'usage de leur destinataire et leur contenu est strictement confidentiel. Une quelconque copie, retransmission, diffusion ou autre usage, ainsi que toute utilisation par des personnes physiques ou morales ou entités autres que le destinataire sont formellement interdits. Si vous recevez ce message par erreur, merci de le détruire et d\'en avertir immédiatement l\'expéditeur. L\'Internet ne permettant pas d\'assurer l\'intégrité de ce message, l\'expéditeur décline toute responsabilité au cas où il aurait été intercepté ou modifié par quiconque.<br> This electronic message and possibly any attachment are transmitted for the exclusive use of their addressee; their content is strictly confidential. Any copy, forward, release or any other use, is prohibited, as well as any use by any unauthorized individual or legal entity. Should you receive this message by mistake, please delete it and notify the sender at once. Because of the nature of the Internet the sender is not in a position to ensure the integrity of this message, therefore the sender disclaims any liability whatsoever, in the event of this message having been intercepted and/or altered.</small></span> </span></td></tr></tbody></table></div></div></div>';

    $signature = file_get_contents("http://bimp.fr/emailing/signatures/signevenementiel2.php?prenomnom=BIMP%20SAV&adresse=Centre%20de%20Services%20Agr%C3%A9%C3%A9%20Apple");

    $tabFilePc = $tabFilePc2 = $tabFilePc3 = array();
    $fileProp = DOL_DATA_ROOT . "/synopsischrono/" . $chrono->id . "/PC-" . $chrono->ref . ".pdf";
    if (is_file($fileProp)) {
        $tabFilePc[] = $fileProp;
        $tabFilePc2[] = ".pdf";
        $tabFilePc3[] = "PC-" . $chrono->ref . ".pdf";
    }

    $tabFileProp = $tabFileProp2 = $tabFileProp3 = array();
    $fileProp = DOL_DATA_ROOT . "/propale/" . $chrono->propal->ref . "/" . $chrono->propal->ref . ".pdf";
    if (is_file($fileProp)) {
        $tabFileProp[] = $fileProp;
        $tabFileProp2[] = ".pdf";
        $tabFileProp3[] = $chrono->propal->ref . ".pdf";
    }


    if (isset($chrono->extraValue[$chrono->id]['Technicien']['value']) && $chrono->extraValue[$chrono->id]['Technicien']['value'] > 0) {
        $userT = new User($db);
        $userT->fetch($chrono->extraValue[$chrono->id]['Technicien']['value']);
        $tech = $userT->getFullName($langs);
    }


    $textSuivie = "\n <a href='".DOL_MAIN_URL_ROOT."/synopsis_chrono_public/page.php?back_serial=" . $chrono->id . "&user_name=" . substr($chrono->societe->name, 0, 3) . "'>Vous pouvez suivre l'intervention ici.</a>";


    if ($type == "Facture") {
        if (is_object($obj))
            $facture = $obj;
        elseif (isset($chrono->propal) && $chrono->propal->id > 0) {
            $tabT = getElementElement("propal", "facture", $chrono->propal->id);
            if (count($tabT) > 0) {
                $facture = new Facture($db);
                $facture->fetch($tabT[count($tabT) - 1]['d']);
                $facture->facnumber = $facture->ref;
            }
        }
        //Envoie mail
        $tabFileFact = $tabFileFact2 = $tabFileFact3 = array();
        $fileProp = DOL_DATA_ROOT . "/facture/" . $facture->facnumber . "/" . $facture->facnumber . ".pdf";
        if (is_file($fileProp)) {
            $tabFileFact[] = $fileProp;
            $tabFileFact2[] = ".pdf";
            $tabFileFact3[] = $facture->facnumber . ".pdf";
        }

        mailSyn2("Fermeture du dossier " . $chrono->ref, $toMail, $fromMail, "Nous vous remercions d'avoir choisi Bimp pour votre '" . $nomMachine . "'.
\nDans les prochains jours, vous allez peut-être recevoir une enquête satisfaction de la part d'APPLE, votre retour est important afin d'améliorer la qualité de notre Centre de Services.
" . $textSuivie . "
\nCordialement.
\nL'équipe BIMP." . $signature, $tabFileFact, $tabFileFact2, $tabFileFact3);
    } elseif ($type == "Devis" && is_object($chrono->propal)) {
        $text = "Bonjour, voici le devis pour la réparation de votre '" . $nomMachine . "'.
\nVeuillez nous communiquer votre accord ou votre refus par retour de ce Mail.
\nSi vous voulez des informations complémentaires, contactez le centre de service par téléphone au " . $tel . " (Appel non surtaxé).";

        if (isset($tech))
            $text .= "\nTechnicien en charge de la réparation : " . $tech . ". \n";

        $text .= $textSuivie . "\n\nCordialement.
\nL'équipe BIMP" . $signature;
        mailSyn2("Devis " . $chrono->ref, $toMail, $fromMail, $text, $tabFileProp, $tabFileProp2, $tabFileProp3);
        //sendSms($chrono, "Bonjour, nous venons d'envoyer votre devis par mail. L'Equipe BIMP.");
    } elseif ($type == "debut") {
        mailSyn2("Prise en charge " . $chrono->ref, $toMail, $fromMail, "Bonjour, merci d'avoir choisi BIMP en tant que Centre de Services Agréé Apple, la référence de votre dossier de réparation est : " . $chrono->ref . ", si vous souhaitez communiquer d'autres informations merci de répondre à ce mail ou de contacter le " . $tel . ".\n" . $textSuivie . "
\n Cordialement."
                . $signature, $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, nous avons le plaisir de vous annoncer que le diagnostic de votre produit commence, nous vous recontacterons quand celui-ci sera fini. L'Equipe BIMP.");
    } elseif ($type == "debDiago") {
        mailSyn2("Prise en charge " . $chrono->ref, $toMail, $fromMail, "Nous avons commencé le diagnostic de votre produit, vous aurez rapidement des nouvelles de notre part. " . $textSuivie . "
\nVotre centre de services Apple." . $signature
                , $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Nous avons commencé le diagnostic de votre produit, vous aurez rapidement des nouvelles de notre part.  Votre centre de services Apple.");
    } elseif ($type == "commOk") {
        mailSyn2("Commande piece(s) " . $chrono->ref, $toMail, $fromMail, "Bonjour,
\nNous venons de commander la/les pièce(s) pour votre '" . $nomMachine . "' ou l'échange de votre iPod,iPad,iPhone. Nous restons à votre disposition pour toutes questions au " . $tel . ".
\nCordialement." . $textSuivie . "

\nL'équipe BIMP" . $signature, $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, la pièce/le produit nécessaire à votre réparation vient d'être commandé(e), nous vous contacterons dès réception de celle-ci. L'Equipe BIMP.");
    } elseif ($type == "repOk") {
        mailSyn2($chrono->ref . " Reparation  terminee", $toMail, $fromMail, "Bonjour, nous avons le plaisir de vous annoncer que la réparation de votre produit est finie. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ", si vous souhaitez plus de renseignements, contactez le " . $tel . ".\n" . $textSuivie . "
\n Cordialement. \n L'Equipe BIMP." . $signature
                , $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, la réparation de votre produit est finie. Vous pouvez le récupérer à " . $nomCentre . " " . $delai . ". L'Equipe BIMP.");
    } elseif ($type == "revPropRefu") {
        mailSyn2("Prise en charge " . $chrono->ref . " terminé", $toMail, $fromMail, "Bonjour, la réparation de votre produit est refusé. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ", si vous souhaitez plus de renseignements, contactez le " . $tel . ".\n\n Cordialement. \n L'Equipe BIMP." . $signature
                , $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, la réparation de votre produit  est refusé. Vous pouvez récupérer votre matériel à " . $nomCentre . " " . $delai . ". L'Equipe BIMP.");
    } elseif ($type == "pieceOk") {
        mailSyn2("Pieces recues " . $chrono->ref, $toMail, $fromMail, "La pièce/le produit que nous avions commandé pour votre Machine est arrivé aujourd'hui. Nous allons commencer la réparation de votre appareil. Vous serez prévenu dès que l'appareil sera prêt.
" . $textSuivie . "
\nCordialement.
\nL'équipe BIMP" . $signature, array(), array(), array());
        sendSms($chrono, "Bonjour, nous venons de recevoir la pièce ou le produit pour votre réparation, nous vous contacterons quand votre matériel sera prêt. L'Equipe BIMP.");
    } elseif ($type == "commercialRefuse") {
        mailSyn2("Devis sav refusé par « " . $chrono->societe->getFullName($langs) . " »", $toMail, $fromMail, "Notre client « " . $chrono->societe->getNomUrl(1) . " » a refusé le devis de réparation sur son « " . $nomMachine . " » pour un montant de «  " . price($chrono->propal->total) . "€ »", array(), array(), array());
    }
}
