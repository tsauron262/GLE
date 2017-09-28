<?php

ini_set('display_errors', 1);
require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/views/render.php';

$fieldvalue = (!empty($id) ? $id : '');
$fieldtype = 'rowid';
if ($user->societe_id) {
    $socid = $user->societe_id;
}

$id = GETPOST('id', 'int');
$object_name = GETPOST('object_name');

$object = null;
$shortlabel = '';
$title = '';
$helpurl = '';
$titre = '';
$icto = '';

switch ($object_name) {
    case 'Product':
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        $object = new Product($db);
        if ($id > 0) {
            $object->fetch($id);
        }
        $result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
        $langs->load("products");
        $title = $langs->trans('ProductServiceCard');
        $shortlabel = dol_trunc($object->label, 16);
        if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
            $title = $langs->trans('Product') . " " . $shortlabel . " - " . $langs->trans('SellingPrices');
            $helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
        }
        if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
            $title = $langs->trans('Service') . " " . $shortlabel . " - " . $langs->trans('SellingPrices');
            $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
        }
        $titre = $langs->trans("CardProduct" . $object->type);
        $picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');
        break;
}

$jsFiles = array(
    '/bimpdatasync/views/js/functions.js',
    '/bimpdatasync/views/js/reports.js',
    '/bimpdatasync/views/js/tab_object.js'
);

llxHeader('', $title, $helpurl, false, false, false, $jsFiles);

echo '<link type="text/css" rel="stylesheet" href="../views/css/font-awesome.css"/>';
echo '<link type="text/css" rel="stylesheet" href="../views/css/styles.css"/>';
echo '<link type="text/css" rel="stylesheet" href="../views/css/reports.css"/>';
echo '<style>.reportRowsContainer {max-height: 250px!important;}</style>';

$head = product_prepare_head($object);
dol_fiche_head($head, 'synchro', $titre, -1, $picto);

if (is_null($object) || !isset($object->id) || !$object->id) {
    echo '<p class="alert alert-danger">Echec du chargement des données</p>';
    exit;
}

$processes_data = BDS_Process::getObjectProcessesData($id, $object_name);

echo renderObjectProcessesData($processes_data);

if (count($processes_data)) {
    global $db;
    $form = new Form($db);

    $date_from = new DateTime();
    $date_from->sub(new DateInterval('P5D'));
    echo '<div style="margin: 30px 0">';
    echo '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '">';
    echo '<h3>Recherche des opérations: </h3>';
    
    echo '<div style="margin: 5px 0">';
    echo '<span class="formLabel">Du: </span>';
    echo '<span class="display: inline-block">';
    echo $form->select_date($date_from->format('Y-m-d H:i:s'), 'from', 1, 1);
    echo '</span>';
    echo '</div>';
    
    echo '<div style="margin: 5px 0">';
    echo '<span class="formLabel">Au: </span>';
    echo '<span class="display: inline-block">';
    echo $form->select_date(date('Y-m-d H:i:s'), 'to', 1, 1);
    echo '</span>';
    echo '</div>';
    
    echo '<div style="margin: 15px 0">';
    echo '<input type="submit" class="button" value="Rechercher"/>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';

    $date = new DateTime();
    $from_title = '';
    $to_title = '';
    if (BDS_Tools::isSubmit('to')) {
        $to_Y = BDS_Tools::getValue('toyear', $date->format('Y'));
        $to_m = BDS_Tools::getValue('tomonth', $date->format('m'));
        $to_d = BDS_Tools::getValue('today', $date->format('d'));
        $to_H = BDS_Tools::getValue('tohour', $date->format('H'));
        $to_i = BDS_Tools::getValue('tomin', $date->format('i'));
        $to_s = '00';
        $to = $to_Y . $to_m . $to_d . '-' . $to_H . $to_i . $to_s;
        $to_title = ' au ' . $to_d . ' / ' . $to_m . ' / ' . $to_Y . ' ' . $to_H . ':' . $to_i;
    } else {
        $to = $date->format('Ymd-His');
        if (BDS_Tools::isSubmit('from')) {
            $to_title = ' à aujourd\'hui';
        } else {
            $to_title .= ' sur les 5 derniers jours';
        }
    }

    if (BDS_Tools::isSubmit('from')) {
        $from_Y = BDS_Tools::getValue('fromyear', $date->format('Y'));
        $from_m = BDS_Tools::getValue('frommonth', $date->format('m'));
        $from_d = BDS_Tools::getValue('fromday', $date->format('d'));
        $from_H = BDS_Tools::getValue('fromhour', $date->format('H'));
        $from_i = BDS_Tools::getValue('frommin', $date->format('i'));
        $from_s = '00';
        $from = $from_Y . $from_m . $from_d . '-' . $from_H . $from_i . $from_s;
        $from_title = ' du ' . $from_d . ' / ' . $from_m . ' / ' . $from_Y . ' ' . $from_H . ':' . $from_i;
    } else {
        $date->sub(new DateInterval('P5D'));
        $from = $date->format('Ymd-His');
    }

    foreach ($processes_data as $process) {
        $title = $process['process_name'] . ' - opérations' . $from_title . $to_title;
        $data = BDS_Report::getObjectNotifications($object_name, $id, $from, $to, null, $process['id_process']);
        $title .= '&nbsp;&nbsp;&nbsp;<span class="badge">' . count($data) . '</span>';
        echo renderObjectNotifications($data, $title);
    }
}