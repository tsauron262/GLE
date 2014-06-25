<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/synopsispanier/class/synopsispanier.class.php');
llxHeader();

$panier= new Synopsispanier($db);
$panier->fetch($_REQUEST["idReferent"], $_REQUEST["type"]);

$para = "idReferent=" . $_REQUEST['idReferent']."&type=".$_REQUEST['type'];
echo "<div class='lienHautDroite2'><a class='butAction' href='".DOL_URL_ROOT."/google/gmaps_all.php?".$para."'> Mode Carte </a></div>";

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





if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'generatePdf' || $_REQUEST['action'] == 'builddoc')) {
        require_once(DOL_DOCUMENT_ROOT . "/synopsispanier/core/modules/synopsispanier/modules_synopsispanier.php");
            $model = (isset($_REQUEST['model']) ? $_REQUEST['model'] : '');
            panier_pdf_create($db, $panier, $model);
            header('location: affichePanier.php?'.$para . "#documentAnchor");
        }

if(isset($_REQUEST["del_element"])){
    $panier->deleteElement($_REQUEST["del_element"]);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'add_element')
    $panier->addElement($_REQUEST["addElement"]);




foreach ($panier->val as $ligne) {
    echo $ligne->getNomUrl(1);
    echo "<br/>";
    echo $ligne->getFullAddress();
    echo "<br/>";
    echo "<a href='?".$para."&del_element=".$ligne->id."'> Supprimer du Panier.</a>";
    echo "<br/>";
    echo "<br/>";
}
$filter= array(0);
foreach ($panier->val as $id => $ligne){
    $filter[]=$id;
}
echo "<form method='post' action='?".$para."&action=add_element'>" ;
echo $form->select_thirdparty('', 'addElement', "rowid not in(".implode(',',$filter).")")."<br />";
echo "<input type='submit' class='butAction' value='Ajouter au Panier'/>";
echo "<br/><br/>";
echo "</form>";


$object = new Object();
$object->ref = $_REQUEST['idReferent'];
            $filename = sanitize_string($object->ref);
            $filedir = $conf->synopsispanier->dir_output . '/' . sanitize_string($object->ref);
            $urlsource = $_SERVER["PHP_SELF"] . "?".$para;
            
            $genallowed = $user->rights->synopsispanier->Global->read;

            require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
            $html = new Form($db);
            $formfile = new FormFile($db);
            $somethingshown = $formfile->show_documents('synopsispanier', $filename, $filedir, $urlsource, $genallowed, $genallowed, "PANIER"); //, $object->modelPdf);
       