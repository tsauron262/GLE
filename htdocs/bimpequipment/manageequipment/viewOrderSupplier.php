<?php

include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimplivraison.class.php';

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/stockAjax.js');

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');


$object = new CommandeFournisseur($db);
$object->fetch($id, $ref);

//$bl = new BimpLivraison($db);
//$bl->fetch($orderId);
//$lignes = $bl->getLignesOrder();

/*
 * View
 */

$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', 'Livrer', $help_url, '', 0, 0, $arrayofjs, $arrayofcss);
BimpCore::displayHeaderFiles();

$form = new Form($db);

/* * *************************************************************************** */
/*                                                                            */
/*                           Mode vue et edition                              */
/*                                                                            */
/* * *************************************************************************** */

if ($id > 0 || !empty($ref)) {
    if ($result >= 0) {
        $object->fetch_thirdparty();

        $author = new User($db);
        $author->fetch($object->user_author_id);

        $head = ordersupplier_prepare_head($object);

        $title = $langs->trans("SupplierOrder");
        dol_fiche_head($head, 'bimpordersupplier', $title, -1, 'order');

        $linkback = '<a href="' . DOL_URL_ROOT . '/fourn/commande/list.php' . (!empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

        $morehtmlref = '<div class="refidno">';
        // Ref supplier
        $morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
        $morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
        // Thirdparty
        $morehtmlref.='<br>' . $langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
        // Project
        if (!empty($conf->projet->enabled)) {
            $langs->load("projects");
            $morehtmlref.='<br>' . $langs->trans('Project') . ' ';
            if (!empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref.='<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
                $morehtmlref.=$proj->ref;
                $morehtmlref.='</a>';
            } else {
                $morehtmlref.='';
            }
        }
        $morehtmlref.='</div>';

        dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

        print '<div class="fichecenter">';
        print '<div class="underbanner clearboth"></div>';


        $cssclass = "titlefield";
        print '</div>';

        dol_fiche_end();
    } else {
        /* Order not found */
        $langs->load("errors");
        print $langs->trans("ErrorRecordNotFound");
    }
}

/**
 * Start Fiche
 */
echo '<div class="page_content container-fluid" style="margin-top: -16px">';

$orderId = $object->id;
print '<input id="id_order_hidden" hidden type="number" value=' . $orderId . '>';

$facid = $id;
if ($object->statut < 3) {
    print '<strong>Veuillez passer cette commande avant de remplir la livraison.</strong>';
    llxFooter();
    $db->close();
    return;
}

if ($object->statut == 5) {
    print '<strong>Cette commande a été livrée. Cependant vous pouvez continuer à rajouter des produits supplémentaires.</strong><br/>';
}

print '<table class="titre"><tr><td>';

print '<strong>Livré dans l\'entrepôt </strong>';

$entrepots = getAllEntrepots($db);

$fk_entrepot = $object->array_options['options_entrepot'];

if ($fk_entrepot == '')
    $fk_entrepot = $user->array_options['options_defaultentrepot'];

print '<select id="entrepot" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id_entrepot => $name) {
    if ($id_entrepot == $fk_entrepot)
        print '<option value="' . $id_entrepot . '" selected>' . $name . '</option>';
    else
        print '<option value="' . $id_entrepot . '">' . $name . '</option>';
}
print '</select> ';

print '</td><td></td><td>';

print '<div id="zoneList"></div>';

print '</td></tr></table>';

print '<table id="productTable" class="noborder objectlistTable" style="margin-top:20px">';
print '<thead><tr class="headerRow">';
print '<th>Numéro groupe</th>';
print '<th>Identifiant produit</th>';
print '<th>Référence</th>';
print '<th>Numéro de série</th>';
print '<th>Label</th>';
print '<th>Quantité total</th>';
print '<th>Quantité restant</th>';
print '<th>Quantité livré</th>';
print '<th>Modifier</th>';
print '<th>Prix unitaire</th>';
print '<th>Mettre en stock <input type="checkbox" name="checkAll"></th>';
print '</tr></thead>';
print '<tbody></tbody>';
print '</table>';

//print '<br/><input id="enregistrer" type="button" class="butAction" value="Enregistrer">';
echo '<div class="buttonsContainer">';
echo '<button type="button" id="enregistrer" class="btn btn-primary"><i class="fa fa-save iconLeft"></i>Enregistrer</button>';
echo '</div>';

print '<div id="alertEnregistrer"></div>';

print '<audio id="bipAudio" preload="auto"><source src="audio/bip.wav" type="audio/mp3" /></audio>';
print '<audio id="bipAudio2" preload="auto"><source src="audio/bip2.wav" type="audio/mp3" /></audio>';
print '<audio id="bipError" preload="auto"><source src="audio/error.wav" type="audio/mp3" /></audio>';


// Liste des réservations: 
echo '<script type="text/javascript">';
echo ' var dol_url_root = \'' . DOL_URL_ROOT . '\';';
echo ' ajaxRequestsUrl = \'' . DOL_URL_ROOT . '/bimpreservation/index.php?fc=commande\';';
echo '</script>';

echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpreservation/views/js/reservation.js"></script>';

$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
$list = new BC_ListTable($reservation, 'commandes', 1, null, 'Statuts des produits');
$list->addFieldFilterValue('rcf.id_commande_fournisseur', (int) $id);
$list->addJoin('br_reservation_cmd_fourn', 'rcf.id_reservation = a.id', 'rcf');
print $list->renderHtml();

echo BimpRender::renderAjaxModal('page_modal');
echo '</div>';

llxFooter();

$db->close();
