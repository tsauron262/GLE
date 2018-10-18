<?php

require_once '../../main.inc.php';

ini_set('display_errors', 1);


require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/BDS_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/views/render.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$fieldvalue = (!empty($id) ? $id : '');
$fieldtype = 'rowid';
if ($user->societe_id) {
    $socid = $user->societe_id;
}

$id = GETPOST('id', 'int');
$object_name = GETPOST('object_name');

$object = null;
$head = null;
$shortlabel = '';
$title = '';
$helpurl = '';
$titre = '';
$picto = '';
$linkback = '';

switch ($object_name) {
    case 'Product':
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
        $object = new Product($db);
        if ($id > 0) {
            $object->fetch($id);
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
            $head = product_prepare_head($object);
        }
        break;

    case 'Categorie':
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/categories.lib.php';

        $langs->load("categories");
        $title = $langs->trans("Categories");
        $result = restrictedArea($user, 'categorie', $id, '&category');
        $picto = 'category';
        $linkback = '<a href="' . DOL_URL_ROOT . '/categories/index.php?leftmenu=cat&type=0">' . $langs->trans("BackToList") . '</a>';

        $object = new Categorie($db);
        if ($id > 0) {
            $object->fetch($id);
            $object->fetch_optionals($id);
//            $extrafields = new ExtraFields($db);
//            $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
            $head = categories_prepare_head($object, 0);
        }
        break;

    case 'Societe':
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

        $langs->load("companies");
        $title = $langs->trans("ThirdParty");
        $result = restrictedArea($user, 'societe', $id, '&societe', '', 'fk_soc', 'rowid', null);
        $help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
        $picto = 'company';
        $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        $object = new Societe($db);
        if ($id > 0) {
            $object->fetch($id);
//            $extrafields = new ExtraFields($db);
//            $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
            $head = societe_prepare_head($object);
        }
        break;

    case 'Contact':
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';

        $langs->load("companies");
        $title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
        $result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid', null);
        $help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
        $picto = 'contact';
        $linkback = '<a href="' . DOL_URL_ROOT . '/contact/list.php">' . $langs->trans("BackToList") . '</a>';

        $object = new Contact($db);
        if ($id > 0) {
            $object->fetch($id);
//            $extrafields = new ExtraFields($db);
//            $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
            $head = contact_prepare_head($object);
        }
        break;

    case 'Commande':
        require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';

        $langs->load('orders');
        $title = $langs->trans('Order');
        $result = restrictedArea($user, 'commande', $id);
        $help_url = 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
        $picto = 'order';
        $linkback = '<a href="' . DOL_URL_ROOT . '/commande/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

        $object = new Commande($db);
        if ($id > 0) {
            $object->fetch($id);
//            $extrafields = new ExtraFields($db);
//            $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
            $head = commande_prepare_head($object);
        }
        break;
}


if (!isset($object->id) || !$object->id) {
    unset($object);
    $object = null;
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

if (is_null($object) || !isset($object->id) || !$object->id) {
    echo '<p class="alert alert-danger">Echec du chargement des données</p>';
    exit;
}

if (!is_null($head)) {
    dol_fiche_head($head, 'synchro', $titre, -1, $picto);
}

if ($linkback) {
//    ($object, $paramid, $morehtml='', $shownav=1, $fieldid='rowid', $fieldref='ref', $morehtmlref='', $moreparam='', $nodbprefix=0, $morehtmlleft='', $morehtmlstatus='', $onlybanner=0, $morehtmlright='')

    dol_banner_tab($object, 'label', $linkback, 0, 'label', 'label', '', '', 0, '', '', 1);
}

$processes_data = BDS_Process::getObjectProcessesData($id, $object_name);

echo renderObjectProcessesData($processes_data);

echo '<div class="buttonsContainer">';
echo '<a class="butAction" href="' . DOL_URL_ROOT . '/bimpdatasync/process.php" target="_blank">';
echo 'Gestion des processus';
echo '</a>';
echo '<a class="butAction" href="' . DOL_URL_ROOT . '/bimpdatasync/rapports.php" target="_blank">';
echo 'Voir tous les rapports';
echo '</a>';
echo '</div>';

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
    echo $form->select_date($date_from->getTimestamp(), 'from', 1, 1);
    echo '</span>';
    echo '</div>';

    echo '<div style="margin: 5px 0">';
    echo '<span class="formLabel">Au: </span>';
    echo '<span class="display: inline-block">';
    echo $form->select_date(time(), 'to', 1, 1);
    echo '</span>';
    echo '</div>';

    echo '<div style="margin: 15px 0">';
    echo '<input type="submit" class="button" value="Rechercher"/>';
    echo '</div>';

    echo '</form>';
    echo '</div>';

    if (BDS_Tools::isSubmit('to')) {
        $date_to = new DateTime(BDS_Tools::getDateTimeFromForm('to'));
    } else {
        $date_to = new DateTime();
    }

    if (BDS_Tools::isSubmit('from')) {
        $date_from = new DateTime(BDS_Tools::getDateTimeFromForm('from'));
    } else {
        $date_from = new DateTime();
        $date_from->sub(new DateInterval('P5D'));
    }

    if ($date_from->getTimestamp() > $date_to->getTimestamp()) {
        echo '<p class="alert alert-danger">La date de début de la recherche doit être inférieure ou égale à la date de fin</p>';
    } else {
        foreach ($processes_data as $process) {
            $title = $process['process_name'] . ' - opérations du ' . $date_from->format('d/m/Y H:i');
            $title .= ' au ' . $date_to->format('d/m/Y H:i');
            $data = BDS_Report::getObjectNotifications($object_name, $id, $date_from->format('Ymd-His'), $date_to->format('Ymd-His'), null, $process['id_process']);
            $title .= '&nbsp;&nbsp;&nbsp;<span class="badge">' . count($data) . '</span>';
            echo renderObjectNotifications($data, $title);
        }
    }
}