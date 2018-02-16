<?php

require("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';



$id = GETPOST('id', 'int');
if ($id < 1)
    $id = $user->id;

$userC = new User($db);
$userC->fetch($id);
$userC->getrights('user');

llxHeader();

$head = user_prepare_head($object);

dol_fiche_head($head, 'formSimple', 'Essentielles', -1, 'user');

if ($user->id == $id) {//On est dans le form de l'utilisateur
    $droitLire = 1;
    $droitModifSimple = 1;
    $droitModif = $userC->rights->user->self->creer;
} else {
    $droitLire = $userC->rights->user->user->lire;
    $droitModifSimple = $userC->rights->user->user->creer;
    $droitModif = $droitModifSimple;
}

//if($droitModif)
//    echo "Vous avez le droit de Modifié tous";
//elseif($droitModifSimple)
//    echo "Vous avez le droit de faire des Modifs Simple";
//elseif($droitModif)
//    echo "Vous avez le droit de lire les infos";
//else($droitLire)
//    echo "Vous n'avez aucun droit";

ini_set('display_errors', 1);

define('BIMP_NEW', 1);
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
BimpCore::displayHeaderFiles();

echo '<script type="text/javascript">';
echo ' var dol_url_root = \'' . DOL_URL_ROOT . '\';';
echo ' ajaxRequestsUrl = \'' . DOL_URL_ROOT . '/bimpcore/index.php\';';
echo '</script>';

$b_user = BimpObject::getInstance('bimpcore', 'Bimp_User', $id);
$view = new BC_View($b_user, 'default');

$full_rights = false;
if ($droitModif) {
    $view->params['edit_form'] = 'default';
} elseif ($droitModifSimple) {
    $view->params['edit_form'] = 'light';
} elseif ($droitLire) {
    $view->params['edit_form'] = null;
}

if ($droitLire) {
    echo $view->renderHtml();
    echo BimpRender::renderAjaxModal('page_modal');
} else
    echo BimpRender::renderAlerts('Vous n\'avez pas la permission de voir cette page');
