<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/home.php
 *  \ingroup    bimpequipment
 *  \brief      Redirect user on multiple link
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/lib/entrepot.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css', '/bimpcore/views/css/bimpcore_bootstrap_new.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/index.js');

$fk_entrepot = GETPOST('boutique');

if ($fk_entrepot == '')
    $fk_entrepot = $user->array_options['options_defaultentrepot'];

/**
 * Functions
 */
function printOptionsBoutique($boutiques, $fk_entrepot) {
    print '<option></option>';

    if ($fk_entrepot != '') {
        foreach ($boutiques as $id => $name) {
            if ($id == $fk_entrepot || $fk_entrepot == $name)
                print '<option value="' . $id . '" selected>' . $name . '</option>';
            else
                print '<option value="' . $id . '">' . $name . '</option>';
        }
    } else {
        foreach ($boutiques as $id => $name) {
            print '<option value="' . $id . '">' . $name . '</option>';
        }
    }
}

/*
 * 	View
 */

llxHeader('', 'Accueil boutique', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Accueil boutique', $linkback);

$boutiques = getAllEntrepots($db);

print '<div id="shopDiv" style="float:left">';
print '<strong>Boutique</strong> ';
print '<select id="warehouseSelect" class="select2 cust" style="width: 200px;">';
printOptionsBoutique($boutiques, $fk_entrepot);
print '</select> ';
print '</div>';

print '<br/>';


print '<br/><div id="allTheFiche" class="fadeInOut">';
print '<div id="ph_links"></div>';


print '</div><br/>';

//echo (int) GETPOST('boutique');
//echo (int) Transfer::STATUS_RECEPTING;

$transfer = BimpObject::getInstance('bimptransfer', 'TransferLine');
BimpObject::loadClass('bimptransfer', 'Transfer');
$list = new BC_ListTable($transfer, 'recap_boutique', 1, null, 'Transfert envoie');
//(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
$list->addFieldFilterValue('t.id_warehouse_source', GETPOST('boutique'));
$list->addFieldFilterValue('t.status', (int) Transfer::STATUS_SENDING);
$list->addJoin('bt_transfer', 'a.id_transfer=t.id', 't');

$list->setAddFormValues(array());
echo $list->renderHtml();

$list = new BC_ListTable($transfer, 'recap_boutique', 1, null, 'Transfert réception');
//(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
$list->addFieldFilterValue('t.id_warehouse_dest', GETPOST('boutique'));
$list->addFieldFilterValue('t.status', (int) Transfer::STATUS_RECEPTING);
$list->addJoin('bt_transfer', 'a.id_transfer=t.id', 't');

$list->setAddFormValues(array());
echo $list->renderHtml();

$db->close();

llxFooter();
