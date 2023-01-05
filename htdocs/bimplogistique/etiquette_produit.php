<?php

require_once("../main.inc.php");
require_once ("../bimpcore/Bimp_Lib.php");
ini_set('display_errors', 1);

global $db, $langs;
$errors = array();

$type = BimpTools::getValue('type', '');

if (!$type) {
    die('Erreur: type d\'étiquette à générer absent');
}

$id_product = (int) BimpTools::getValue('id_product', 0);
$id_products = array();

if (BimpTools::getValue('id_products', '') != '') {
    $id_products = explode(',', BimpTools::getValue('id_products', ''));
}

if (!$id_product && count($id_products) == 0) {
    die('Erreur: ID du produit absent');
}

if (count($id_products) == 0) {
    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);

    if (!BimpObject::objectLoaded($product)) {
        die('Erreur: Le produit d\'ID ' . $id_product . ' n\'existe pas');
    }
} else {
    $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
}

$qty = (int) BimpTools::getValue('qty', 1);

$pdf = null;
switch ($type) {
    case 'stock':
        require_once './pdf/classes/EtiquetteProd1.php';
        $pdf = new EtiquetteProd1($db);
        break;

    case 'magasin':
        require_once './pdf/classes/EtiquetteProd2.php';
        $pdf = new EtiquetteProd2($db);
        break;

    default:
        die('Erreur: type d\'étiquette invalide: "' . $type . '"');
}

$pdf->qty_etiquettes = $qty;
if (count($id_products) == 0)
    $pdf->init($product->dol_object);
else
    $pdf->init($id_products);


if ($pdf->render('etiquette_prod_' . $type . '_' . $id_product . '.pdf', true, true)) {
    exit;
}

if (count($pdf->errors)) {
    $pdf->displayErrors();
}