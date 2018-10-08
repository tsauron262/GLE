<?php

require("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';



$id = GETPOST('id', 'int');
if ($id < 1)
    $id = $user->id;



if ($user->id == $id) {//On est dans le form de l'utilisateur
    $object = $user;
    $droitLire = 1;
    $droitModifSimple = 1;
    $droitModif = 0;//$object->rights->user->self->creer;
} else {
    $object = new User($db);
    $object->fetch($id);
    $object->getrights('user');
    $droitLire = $user->rights->user->user->lire;
    $droitModifSimple = $user->rights->user->user->creer;
    $droitModif = $droitModifSimple;
}


llxHeader();

$head = user_prepare_head($object);

dol_fiche_head($head, 'formSimple', 'Essentielles', -1, 'user');

ini_set('display_errors', 1);

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
BimpCore::displayHeaderFiles();

echo '<script type="text/javascript">';
echo ' var dol_url_root = \'' . DOL_URL_ROOT . '\';';
echo ' ajaxRequestsUrl = \'' . DOL_URL_ROOT . '/bimpcore/index.php\';';
echo '</script>';

$b_user = BimpObject::getInstance('bimpcore', 'Bimp_User', $id);
$view = new BC_View($b_user, 'default', false, 1, 'Utilisateur ' . $b_user->getData('login'));

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

llxFooter();
