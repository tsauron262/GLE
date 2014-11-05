<?php

require '../main.inc.php';


global $tabCentre;

require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/Chrono.class.php");

require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
include_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");


$chrono = new Chrono($db);
$chrono->fetch($_REQUEST['id']);
$chrono->getValues();
$chrono->getValuesPlus();
$nomMachine = '';

$_REQUEST['sendSms'] = (isset($_REQUEST['sendSms']) && ($_REQUEST['sendSms'] == "checked") || $_REQUEST['sendSms'] == "true");


$tabFileProp = $tabFileProp2 = $tabFileProp3 = array();
if (isset($chrono->propal) && isset($chrono->propal->ref)) {
    $fileProp = DOL_DATA_ROOT . "/propale/" . $chrono->propal->ref . "/" . $chrono->propal->ref . ".pdf";
    if (is_file($fileProp)) {
        $tabFileProp[] = $fileProp;
        $tabFileProp2[] = ".pdf";
        $tabFileProp3[] = $chrono->propal->ref . ".pdf";
    }
    $propal = $chrono->propal;
}




$tabFilePc = $tabFilePc2 = $tabFilePc3 = array();
$fileProp = DOL_DATA_ROOT . "/synopsischrono/" . $chrono->id . "/PC-" . $chrono->ref . ".pdf";
if (is_file($fileProp)) {
    $tabFilePc[] = $fileProp;
    $tabFilePc2[] = ".pdf";
    $tabFilePc3[] = "PC-" . $chrono->ref . ".pdf";
}
//die($fileProp);
//echo "<pre>".$fileProp;
//print_r($tabT[0]);
//die;



if (isset($chrono->valuesPlus[1039]->value) && $chrono->valuesPlus[1039]->value) {
    $prod = new Chrono($db);
    $prod->fetch($chrono->valuesPlus[1039]->value);
    $nomMachine = $prod->description;
}

$idEtat = 1056;

$ok = $attentePiece = false;

$idEntrepot = null;

if (isset($chrono->extraValue[$chrono->id]['Centre']['value']) && isset($tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']])) {
    $tel = $tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']][0];
    $fromMail = "SAV BIMP<" . $tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']][1] . ">";
    $sql = $db->query("SELECT * FROM  `" . MAIN_DB_PREFIX . "entrepot` WHERE  `label` LIKE  'SAV" . $chrono->extraValue[$chrono->id]['Centre']['value'] . "'");
    if ($db->num_rows($sql) > 0) {
        $result = $db->fetch_object($sql);
        $idEntrepot = $result->rowid;
    }
} else {
    $tel = "N/C";
    $fromMail = "SAV BIMP<no-replay@bimp.fr>";
}

if (isset($chrono->extraValue[$chrono->id]['Technicien']['value']) && $chrono->extraValue[$chrono->id]['Technicien']['value'] > 0) {
    $userT = new User($db);
    $userT->fetch($chrono->extraValue[$chrono->id]['Technicien']['value']);
    $tech = $userT->getFullName($langs);
}

if (isset($_REQUEST['actionEtat'])) {
    $action = $_REQUEST['actionEtat'];

    if (isset($chrono->contact) && isset($chrono->contact->email) && $chrono->contact->email != '')
        $toMail = $chrono->contact->email;
    else
        $toMail = $chrono->societe->email;

    if ($chrono->note == "N/C")
        $chrono->note = "";

    if ($action == "devisOk" && $chrono->propal->id > 0 && $chrono->extraValue[$chrono->id]['Etat']['value'] != 3) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Devis accepté le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->propal->cloture($user, 2, "Auto via SAV");
        $chrono->setDatas($chrono->id, array($idEtat => 3));
        $ok = true;
    }

    if ($action == "debDiago" && $chrono->extraValue[$chrono->id]['Etat']['value'] != 5) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Diagnostique commencé le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 5, 1046 => $user->id));
        $ok = true;
        if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms'])
            mailSyn2("Prise en charge " . $chrono->ref, $toMail, $fromMail, "Bonjour, merci d'avoir choisi BIMP en tant que Centre de Services Agrée Apple, la référence de votre dossier de réparation est : " . $chrono->ref . ", si vous souhaitez communiquer d'autres informations merci de répondre à ce mail ou de contacter le " . $tel . ".\n\n Cordialement."
                    , $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, nous avons le plaisir de vous annoncer que le diagnostic de votre produit commence, nous vous recontacterons quand celui-ci sera fini. L'Equipe BIMP.");
    }

    if ($action == "commandeOK" && $chrono->extraValue[$chrono->id]['Etat']['value'] == 1) {
        header("Location:fiche.php?id=" . $_GET['id'] . "&msg=" . urlencode("Attention, le SAV été deja au statut Attente Pièce !"));
        die;
    }




    if ($action == "commandeOK" && $chrono->propal->id > 0){// && $chrono->extraValue[$chrono->id]['Etat']['value'] != 1) {
        //Si commmande apple a 0 on passse la propal sous garenti.
        if($chrono->extraValue[$chrono->id]['Etat']['value'] < 9){
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
            mailSyn2("Commande piece(s) " . $chrono->ref, $toMail, $fromMail, "Bonjour,
\nNous venons de commander la/les pièce(s) pour votre '" . $nomMachine . "' ou l'échange de votre iPod,iPad,iPhone. Nous restons à votre disposition pour toutes questions au " . $tel . ".
\nCordialement.
\nL'équipe BIMP", array(), array(), array());
        sendSms($chrono, "Bonjour, la pièce/le produit nécessaire à votre réparation vient d'être commandé(e), nous vous contacterons dès réception de celle-ci. L'Equipe BIMP.");
        }
        $ok = true;
    }

    if ($action == "revProp" && $chrono->propal->id > 0) {
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Revision/revision.class.php");
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Devis révisé aprés fermeture le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);

        $revision = new SynopsisRevisionPropal($chrono->propal);
        $revision->reviserPropal($_REQUEST['ligne'] ? array(array('Diagnostic'), null) : array(null, array('acompte')));
//$propal = new Propal();
        if ($_REQUEST['ligne'] == 0) {//Création de la facture de frais de prise en charge.
            $propal->addline("Prise en charge :  : " . $chrono->ref .
                    "\n" . "Machine : " . $nomMachine .
                    "\n" . "Frais de gestion devis refusé.
", $_REQUEST['frais'] / 1.20, 1, 20, 0, 0, 0, 0, 'HT', null, null, 1);



            $propal->fetch($chrono->propal->id);
            propale_pdf_create($db, $propal, "azurSAV", $langs);



//            $facture = new Facture($db);
//            $facture->createFromOrder($propal);
//            $facture->validate($user, '', $idEntrepot);
//            $facture->fetch($facture->id);
//            facture_pdf_create($db, $facture, "crabeSav", $langs);
//            link(DOL_DATA_ROOT . "/facture/" . $facture->ref . "/" . $facture->ref . ".pdf", DOL_DATA_ROOT . "/synopsischrono/" . $chrono->id . "/" . $facture->ref . ".pdf");
//            $propal->cloture($user, 4, '');
            $chrono->setDatas($chrono->id, array($idEtat => 9));
            $chrono->propal->cloture($user, 2, "Auto via SAV");

            $delai = (isset($_REQUEST['nbJours']) && $_REQUEST['nbJours'] > 0 ? "dans " . $_REQUEST['nbJours'] . " jours" : "dès maintenant");
            if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms'])
                mailSyn2("Prise en charge " . $chrono->ref . " terminé", $toMail, $fromMail, "Bonjour, la réparation de votre produit est refusé. Vous pouvez récupérer votre matériel " . $delai . ", si vous souhaitez plus de renseignements, contactez le " . $tel . ".\n\n Cordialement. \n L'Equipe BIMP."
                        , $tabFilePc, $tabFilePc2, $tabFilePc3);
            sendSms($chrono, "Bonjour, la réparation de votre produit  est refusé. Vous pouvez récupérer votre matériel " . $delai . ". L'Equipe BIMP.");
        } else {

            $chrono->setDatas($chrono->id, array($idEtat => 5));
        }



//        $chrono->propal->cloture($user, 5, "Auto via SAV");
        $ok = true;
    }


    if ($action == "devisKo" && $chrono->propal->id > 0 && $chrono->extraValue[$chrono->id]['Etat']['value'] != 9) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Devis refusé le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->propal->cloture($user, 3, "Auto via SAV");
        $chrono->setDatas($chrono->id, array($idEtat => 6));
        $ok = true;
    }
    if ($action == "pieceOk" && $chrono->extraValue[$chrono->id]['Etat']['value'] != 4) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Pièce reçue le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 4));
        $ok = true;
        if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms'])
            mailSyn2("Pièces reçues " . $chrono->ref, $toMail, $fromMail, "La pièce/le produit que nous avions commandé pour votre Machine est arrivé aujourd'hui. Nous allons commencer la réparation de votre appareil. Vous serez prévenu dès que l'appareil sera prêt.
\nCordialement.
\nL'équipe BIMP", array(), array(), array());
        sendSms($chrono, "Bonjour, nous venons de recevoir la pièce ou le produit pour votre réparation, nous vous contacterons quand votre matériel sera prêt. L'Equipe BIMP.");
    }


    if ($action == "repEnCours" && $chrono->extraValue[$chrono->id]['Etat']['value'] != 4) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Réparation en cours depuis le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 4));
        $ok = true;
    }

    if ($action == "repOk" && $chrono->extraValue[$chrono->id]['Résolution']['value'] == "") {
        header("Location:fiche.php?id=" . $_GET['id'] . "&msg=" . urlencode("Veuillez compléter  résolution svp!"));
        die;
    }


    if ($action == "repOk" && $chrono->extraValue[$chrono->id]['Etat']['value'] != 9) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Réparation terminée le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 9));
        $ok = true;


//        $propal->cloture($user, 3, '');
        $delai = (isset($_REQUEST['nbJours']) && $_REQUEST['nbJours'] > 0 ? "dans " . $_REQUEST['nbJours'] . " jours" : "dès maintenant");
        if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms'])
            mailSyn2("Prise en charge " . $chrono->ref . " terminé", $toMail, $fromMail, "Bonjour, nous avons le plaisir de vous annoncer que la réparation de votre produit est fini. Vous pouvez récupérer votre matériel " . $delai . ", si vous souhaitez plus de renseignements, contactez le " . $tel . ".\n\n Cordialement. \n L'Equipe BIMP."
                    , $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, nous avons le plaisir de vous annoncer que la réparation de votre produit est fini. Vous pouvez récupérer votre matériel " . $delai . ". L'Equipe BIMP.");
    }


    if ($action == "attenteClient1" && $chrono->extraValue[$chrono->id]['Diagnostic']['value'] == "") {
        header("Location:fiche.php?id=" . $_GET['id'] . "&msg=" . urlencode("Veuillez compléter diagnostic svp!"));
        die;
    }


    if (($action == "attenteClient1" || $action == "attenteClient2") && ($chrono->extraValue[$chrono->id]['Etat']['value'] != 3 || $chrono->extraValue[$chrono->id]['Etat']['value'] != 2)) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Devis validé depuis le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->propal->addline("Diagnostic : " . stripslashes($chrono->extraValue[$chrono->id]['Diagnostic']['value']), 0, 1, 0, 0, 0, 0, 0, 'HT', 0, 0, 3);
        if ($action == "attenteClient2") {
//            die($totPa);
//            ($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $price_base_type='HT', $pu_ttc=0, $info_bits=0, $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=0, $pa_ht=0
//            $totHt = $chrono->propal->total_ht;
//            $totTtc = $chrono->propal->total_ttc;
//            $tabT = $chrono->propal->InvoiceArrayList($chrono->propal->id);
//            if (isset($tabT[0]) && isset($tabT[0]->facnumber) && stripos($tabT[0]->facnumber, "AC") !== false) {
//                $totHt += $tabT[0]->total;
//                $totTtc += $tabT[0]->total*1.2;
//            } 
            $totPa = 0;
            $totHt = 0;
            $totTtc = 0;
            foreach ($propal->lines as $ligne) {
                if ($ligne->desc != "Acompte" && $ligne->ref != "SAV-PCU") {
                    $totHt += $ligne->total_ht;
                    $totTtc += $ligne->total_ttc;
                    $totPa += $lines->pa_ht;
                }
            }


            $chrono->propal->addline("Garantie", -($totHt), 1, (($totTtc / ($totHt != 0 ? $totHt : 1) - 1) * 100), 0, 0, 0, 0, 'HT', 0, 0, 1, -1, 0, 0, 0, -$totHt);
            if ($attentePiece != 1)//Sinon on vien de commander les piece sous garentie
                $chrono->setDatas($chrono->id, array($idEtat => 3));
            $chrono->propal->valid($user);
            $chrono->propal->cloture($user, 2, "Auto via SAV sous garentie");
            $chrono->propal->fetch($chrono->propal->id);
            propale_pdf_create($db, $chrono->propal, "azurSAV", $langs);
        } else {
            $chrono->propal->fetch($chrono->propal->id);
            $chrono->propal->valid($user);
            propale_pdf_create($db, $chrono->propal, "azurSAV", $langs);
            $text = "Bonjour, voici le devis pour la réparation de votre '" . $nomMachine . "'.
\nVeuillez nous communiquer votre accord ou votre refus par retour de ce Mail.
\nSi vous voulez des informations complémentaires, contactez le centre de service par téléphone au " . $tel . " (Appel non surtaxé).";

            if (isset($tech))
                $text .= "\nTechnicien en charge de la réparation : " . $tech . ". \n";

            $text .= "\n\nCordialement.
\nL'équipe BIMP";
            if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms'])
                mailSyn2("Devis " . $chrono->ref, $toMail, $fromMail, $text, $tabFileProp, $tabFileProp2, $tabFileProp3, $fromMail);
            $chrono->setDatas($chrono->id, array($idEtat => 2));
        }
        $ok = true;
    }

    if ($action == "restituer" && $propal->total_ttc > 0 && !(isset($_REQUEST['modeP']) && $_REQUEST['modeP'] > 0)) {
        header("Location:fiche.php?id=" . $_GET['id'] . "&msg=" . urlencode("Attention, " . price($propal->total_ttc) . " € A payer, merci de remplir le moyen de paiement !"));
        die;
    }


    if ($action == "restituer" && $chrono->extraValue[$chrono->id]['Etat']['value'] != 999) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Restitué le " . date('d-m-y H:i') . " par " . $user->getFullName($langs);
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 999));
        $ok = true;



        $facture = new Facture($db);
        $facture->createFromOrder($propal);
//        $facture->create($user);
        $facture->addline("Résolution : " . stripslashes($chrono->extraValue[$chrono->id]['Résolution']['value']), 0, 1, 0, 0, 0, 0, 0, null, null, null, null, null, 'HT', 0, 3);
        $facture->validate($user, '', $idEntrepot);
        $facture->fetch($facture->id);




        if ($facture->total_ttc - $facture->getSommePaiement() == 0 || (isset($_REQUEST['modeP']) && $_REQUEST['modeP'] > 0 && $_REQUEST['modeP'] != 56)) {
            require_once(DOL_DOCUMENT_ROOT . "/compta/paiement/class/paiement.class.php");
            $payement = new Paiement($db);
            $payement->amounts = array($facture->id => $facture->total_ttc - $facture->getSommePaiement());
            $payement->datepaye = dol_now();
            $payement->paiementid = $_REQUEST['modeP'];
            $payement->create($user);
            $facture->set_paid($user);
            facture_pdf_create($db, $facture, "crabeSav", $langs);
        }


        $chrono->propal->cloture($user, 2, "Auto via SAV");



        //Generation
        facture_pdf_create($db, $facture, "crabeSav", $langs);
//        addElementElement("propal", "facture", $propal->id, $facture->id);
        link(DOL_DATA_ROOT . "/facture/" . $facture->ref . "/" . $facture->ref . ".pdf", DOL_DATA_ROOT . "/synopsischrono/" . $chrono->id . "/" . $facture->ref . ".pdf");

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
\nCordialement.
\nL'équipe BIMP.", $tabFileFact, $tabFileFact2, $tabFileFact3);
    }
}

if ($ok)
//    header("Location:fiche.php?id=" . $_GET['id']);
    header("Location:" . $_SERVER["HTTP_REFERER"]);
else {
    dol_syslog("Page request des chrono sav sans parametre action vamide trouvé Ancien etat : " . $chrono->extraValue[$chrono->id]['Etat']['value'] . " Nouveau : " . $action, 4);
    echo "Quelque chose c'est mal passé : ";
}

function sendSms($chrono, $text) {
    if (isset($_REQUEST['sendSms']) && $_REQUEST['sendSms']) {
        if (is_object($chrono->contact) && $chrono->contact->phone_mobile != "")
            $to = $chrono->contact->phone_mobile;
        elseif (is_object($chrono->societe) && $chrono->societe->phone != "")
            $to = $chrono->societe->phone;
        $fromsms = urlencode('SAV BIMP');

        $to = str_replace(" ", "", $to);

        if ($to == "" || stripos($to, "6") === false)
            return 0;


//    echo $to . "   |   " . $text;
//    die;
        if (stripos($to, "+") === false)
            $to = "+33" . substr($to, 1, 10);

//    require_once(DOL_DOCUMENT_ROOT . "/core/class/CSMSFile.class.php");
//    $smsfile = new CSMSFile($to, $fromsms, $text);
//    echo $smsfile->sendfile();

        return 1;
    }
}
