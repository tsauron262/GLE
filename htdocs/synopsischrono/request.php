<?php

require '../main.inc.php';


global $tabCentre;

require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/Chrono.class.php");
$chrono = new Chrono($db);
$chrono->fetch($_REQUEST['id']);
$chrono->getValues();
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
}


$tabFileFact = $tabFileFact2 = $tabFileFact3 = array();
if (isset($chrono->propal)) {
    $tabT = $chrono->propal->InvoiceArrayList($chrono->propal->id);
    if (isset($tabT[0]) && isset($tabT[0]->facnumber)) {
        $fact = $tabT[count($tabT) - 1];
        $fileProp = DOL_DATA_ROOT . "/facture/" . $fact->facnumber . "/" . $fact->facnumber . ".pdf";
        if (is_file($fileProp)) {
            $tabFileFact[] = $fileProp;
            $tabFileFact2[] = ".pdf";
            $tabFileFact3[] = $fact->facnumber . ".pdf";
        }
    }
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



if (isset($chrono->extraValue[$chrono->id]['Materiel']['value']) && $chrono->extraValue[$chrono->id]['Materiel']['value']) {
    $prod = new Chrono($db);
    $prod->fetch($chrono->extraValue[$chrono->id]['Materiel']['value']);
    $nomMachine = $prod->description;
}
$idEtat = 1056;

$ok = false;

$tel = $tabCentre[$chrono->extraValue[$chrono->id]['Centre']['value']][0];

if (isset($_REQUEST['actionEtat'])) {
    $action = $_REQUEST['actionEtat'];

    $fromMail = "SAV BIMP<no-replay@bimp.fr>";
    if (isset($chrono->contact) && isset($chrono->contact->email) && $chrono->contact->email != '')
        $toMail = $chrono->contact->email;
    else
        $toMail = $chrono->societe->email;

    if ($chrono->note == "N/C")
        $chrono->note = "";

    if ($action == "devisOk" && $chrono->propal->id > 0) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Devis accepté le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $chrono->propal->cloture($user, 2, "Auto via SAV");
        $chrono->setDatas($chrono->id, array($idEtat => 3));
        $ok = true;
    }

    if ($action == "debDiago") {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Diagnostique commencé le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $chrono->setDatas($chrono->id, array($idEtat => 5));
        $ok = true;
        mailSyn2("Prise en charge " . $chrono->ref, $toMail, $fromMail, "Bonjour, merci d'avoir choisi BIMP en tant que Centre de Services Agrée Apple, la référence de votre dossier de réparation est : " . $chrono->ref . ", si vous souhaitez plus de renseignements, contactez le ".$tel.".\n\n Cordialement."
                , $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, nous avons le plaisir de vous annoncer que le diagnostic de votre produit commence, nous vous recontacterons quand celui-ci sera fini. L'Equipe BIMP.");
    }

    if ($action == "commandeOK" && $chrono->propal->id > 0) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Piéce commandée le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 1));
        $ok = true;
        mailSyn2("Commande pièce(s) " . $chrono->ref, $toMail, $fromMail, "Bonjour,
\nNous venons de commander la/les pièce(s) pour votre 'ModèleMachine' ou l'échange de votre iPod,iPad,iPhone. Nous restons à votre disposition pour toutes questions au ".$tel.".
\nCordialement.
\nL'équipe BIMP");
        sendSms($chrono, "Bonjour, la pièce/le produit nécessaire à votre réparation vient d'être commandé(e), nous vous contacterons dès réception de celle-ci. L'Equipe BIMP.");
    }
    if ($action == "devisKo" && $chrono->propal->id > 0) {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Devis refusé le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        require_once(DOL_DOCUMENT_ROOT . "/core/modules/propale/modules_propale.php");
        $chrono->propal->cloture($user, 3, "Auto via SAV");
        $chrono->setDatas($chrono->id, array($idEtat => 9));
        $ok = true;
    }
    if ($action == "pieceOk") {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Pièce reçue le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 4));
        $ok = true;
        mailSyn2("Pièces reçues " . $chrono->ref, $toMail, $fromMail, "La pièce/le produit que nous avions commandé pour votre Machine est arrivé aujourd'hui. Nous allons commencer la réparation de votre appareil. Vous serez prévenu dès que l'appareil sera prêt.
\nCordialement.
\nL'équipe BIMP");
        sendSms($chrono, "Bonjour, nous venons de recevoir la pièce ou le produit pour votre réparation, nous vous contacterons quand votre matériel sera prêt. L'Equipe BIMP.");
    }
    if ($action == "repOk") {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Réparation terminée le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 9));
        $ok = true;

        $propal = new Propal($db);
        $propal = $chrono->propal;

        require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
        $facture = new Facture($db);
        $facture->createFromOrder($propal);
//        $facture->create($user);
        $facture->validate($user);
        facture_pdf_create($db, $facture, null, $langs);
//        addElementElement("propal", "facture", $propal->id, $facture->id);
        link(DOL_DATA_ROOT . "/facture/" . $facture->ref . "/" . $facture->ref . ".pdf", DOL_DATA_ROOT . "/synopsischrono/" . $chrono->id . "/" . $facture->ref . ".pdf");
        $propal->cloture($user, 4, '');
        mailSyn2("Prise en charge " . $chrono->ref." terminé", $toMail, $fromMail, "Bonjour, nous avons le plaisir de vous annoncer que la réparation de votre produit est fini. Vous pouvez récupérer votre matériel dès maintenant, si vous souhaitez plus de renseignements, contactez le ".$tel.".\n\n Cordialement. \n L'Equipe BIMP."
                , $tabFilePc, $tabFilePc2, $tabFilePc3);
        sendSms($chrono, "Bonjour, nous avons le plaisir de vous annoncer que la réparation de votre produit est fini. Vous pouvez récupérer votre matériel dès maintenant. L'Equipe BIMP.");
    }
    if ($action == "attenteClient1") {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Attente client depuis le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 2));
        $chrono->propal->valid($user);
        $ok = true;
        mailSyn2("Devis " . $chrono->ref, $toMail, $fromMail, "Bonjour, voici le devis pour la réparation de votre '" . $nomMachine . "'.
\nVeuillez nous communiquer votre accord ou votre refus par retour de ce Mail.
\nSi vous voulez des informations complémentaires, contactez le centre de service par téléphone au ".$tel." (Appel non surtaxé).
\nCordialement.
\nL'équipe BIMP", $tabFileProp, $tabFileProp2, $tabFileProp3);
    }

    if ($action == "restituer") {
        $chrono->note = (($chrono->note != "") ? $chrono->note . "\n\n" : "");
        $chrono->note .= "Restitué le " . date('d-m-y H:i');
        $chrono->update($chrono->id);
        $chrono->setDatas($chrono->id, array($idEtat => 999));
        $ok = true;
        mailSyn2("Fermeture du dossier " . $chrono->ref, $toMail, $fromMail, "Nous vous remercions d'avoir choisi Ephésus pour votre 'ModèleMachine'.
\nDans les prochains jours, vous allez peut-être recevoir une enquête satisfaction de la part d'APPLE, votre retour est important afin d'améliorer la qualité de notre Centre de Services.
\nCordialement.
\nL'équipe BIMP.", $tabFileFact, $tabFileFact2, $tabFileFact3);
        $tabT = getElementElement("propal", "facture", $chrono->propalid);
        $facture = new Facture($db);
        $facture->fetch($tabT[count($tabT) - 1]['d']);
        require_once(DOL_DOCUMENT_ROOT . "/compta/paiement/class/paiement.class.php");
        $payement = new Paiement($db);
        $payement->amounts = array($facture->id => $facture->total_ttc);
        $payement->datepaye = dol_now();
        $payement->paiementid = $_REQUEST['modeP'];
        $payement->create($user);
        $facture->set_paid($user);
        include_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
        facture_pdf_create($db, $facture, null, $langs);
    }
}

if ($ok)
    header("Location:fiche.php?id=" . $_GET['id']);

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
