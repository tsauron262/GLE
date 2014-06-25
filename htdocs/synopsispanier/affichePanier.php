<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../main.inc.php');
llxHeader();



if (isset($_REQUEST['type']) && $_REQUEST['type'] == "tiers" && $_REQUEST['idReferent'] > 0) {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
    $object = new Societe($db);
    $object->fetch($_REQUEST['idReferent']);
    $head = societe_prepare_head($object);
    dol_fiche_head($head, 'panier', $langs->trans("ThirdParty"), 0, 'company');



    $form = new Form($db);
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

    print '<table class="border" width="100%">';

    print '<tr><td width="25%">' . $langs->trans('ThirdPartyName') . '</td>';
    print '<td colspan="3">';
    print $form->showrefnav($object, 'type=' . $_REQUEST['type'] . '&idReferent', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
    print '</td></tr>';

    if (!empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
        print '<tr><td>' . $langs->trans('Prefix') . '</td><td colspan="3">' . $object->prefix_comm . '</td></tr>';
    }

    if ($object->client) {
        print '<tr><td>';
        print $langs->trans('CustomerCode') . '</td><td colspan="3">';
        print $object->code_client;
        if ($object->check_codeclient() <> 0)
            print ' <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
        print '</td></tr>';
    }

    if ($object->fournisseur) {
        print '<tr><td>';
        print $langs->trans('SupplierCode') . '</td><td colspan="3">';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0)
            print ' <font class="error">(' . $langs->trans("WrongSupplierCode") . ')</font>';
        print '</td></tr>';
    }

    print "</table></form> ";
}

$para = "idReferent=" . $_REQUEST['idReferent'] . "&type=" . $_REQUEST['type'];

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'generatePdf' || $_REQUEST['action'] == 'builddoc')) {
//    if ($conf->global->MAIN_MODULE_BABELGA == 1 && $_REQUEST['id'] > 0 && ($object->typepanier == 6 || $object->typepanier == 5)) {
//        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsispanier/modules_panierGA.php");
//        panierGA_pdf_create($db, $object->id, $_REQUEST['model']);
//    } else {//if ($conf->global->MAIN_MODULE_BABELGMAO == 1 && $_REQUEST['id'] > 0 && ($object->typepanier == 7 || $object->typepanier == 2 || $object->typepanier == 3 || $object->typepanier == 4)) {
    require_once(DOL_DOCUMENT_ROOT . "/synopsispanier/core/modules/synopsispanier/modules_synopsispanier.php");
    $model = (isset($_REQUEST['model']) ? $_REQUEST['model'] : '');

    panier_pdf_create($db, $_REQUEST['idReferent'], $model);
//    } else {
//        require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsispanier/modules_synopsispanier.php");
//        panier_pdf_create($db, $object->id, $_REQUEST['model']);
//    }
    header('location: affichePanier.php?' . $para . "#documentAnchor");
}


$societe = new Societe($db);
$requeteMomo = "SELECT valeur FROM " . MAIN_DB_PREFIX . "Synopsys_Panier where type='" . $_REQUEST['type'] . "' and referent = " . $_REQUEST['idReferent'] . ";";
$result = $db->query($requeteMomo);
while ($ligne = $db->fetch_object($result)) {
    $societe->fetch($ligne->valeur);
    echo $societe->getNomUrl(1);
    echo "<br/>";
    echo $societe->getFullAddress();
    echo "<br/>";
    echo "<br/>";
}


dol_fiche_end();

$para = "idReferent=" . $_REQUEST['idReferent'] . "&type=" . $_REQUEST['type'];
$object = new Object();
$object->ref = $_REQUEST['idReferent'];
$filename = sanitize_string($object->ref);
$filedir = $conf->synopsispanier->dir_output . '/' . sanitize_string($object->ref);
$urlsource = $_SERVER["PHP_SELF"] . "?" . $para;

$genallowed = $user->rights->synopsispanier->Global->read;

require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
$html = new Form($db);
$formfile = new FormFile($db);
$somethingshown = $formfile->show_documents('synopsispanier', $filename, $filedir, $urlsource, $genallowed, $genallowed, "PANIER"); //, $object->modelPdf);
 