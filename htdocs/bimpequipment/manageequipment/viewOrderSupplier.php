<?php

include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimplivraison.class.php';

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

/*
 * View
 */

$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', 'Livrer', $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

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
$orderId = $object->id;
print '<input id="id_order_hidden" hidden type="number" value=' . $orderId . '>';

$facid = $id;
if ($object->statut < 3) {
    print '<strong>Veuillez passer cette commande avant de remplir la livraison.</strong>';
    return;
}
if (4 < $object->statut) {
    if (5 == $object->statut)
        print '<strong>Cette commande a déjà été livrée.</strong>';
    else
        print '<strong>Cette commande a été annulée.</strong>';

    llxFooter();
    $db->close();
    return;
}
print '<strong>Livré dans l\'entrepôt </strong>';

$entrepots = getAllEntrepots($db);

print '<select id="entrepot" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select> ';

$bl = new BimpLivraison($db);
$bl->fetch($orderId);
$lignes = $bl->getLignesOrder();


print '</table>';

print '<div class="object_list_table">';
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


//print '<div class="object_list_table">';
//print '<table id="productDeliveredTable" class="noborder objectlistTable" style="margin-top:20px">';
//print '<tbody></tbody>';
//print '</table>';


print '<br/><input id="enregistrer" type="button" class="butAction" value="Enregistrer">';

print '<br/><div id="alertEnregistrer"></div><br/><br/><br/>';

print '<audio id="bipAudio" preload="auto"><source src="audio/bip.wav" type="audio/mp3" /></audio>';
print '<audio id="bipAudio2" preload="auto"><source src="audio/bip2.wav" type="audio/mp3" /></audio>';
print '<audio id="bipError" preload="auto"><source src="audio/error.wav" type="audio/mp3" /></audio>';

llxFooter();

$db->close();
