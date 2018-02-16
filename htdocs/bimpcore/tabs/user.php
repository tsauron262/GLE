<?php

require("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';



$id = GETPOST('id', 'int');
if ($id < 1)
    $id = $user->id;


if ($user->id == $id) {//On est dans le form de l'utilisateur
    $droitLire = 1;
    $droitModifSimple = 1;
    $droitModif = $user->rights->user->self->creer;
    $object = $user;
$droitModif = 0;//A suupr pour montrée....
} else {
    $droitLire = $user->rights->user->user->lire;
    $droitModifSimple = $user->rights->user->user->creer;
    $droitModif = $droitModifSimple;
    $object = new User($db);
    $object->fetch($id);
    $object->getrights('user');
}


llxHeader();

$head = user_prepare_head($object);

dol_fiche_head($head, 'formSimple', 'Essentiel', -1, 'user');



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

if ($droitModif) {
    $view->params['edit_form'] = 'default';
} elseif ($droitModifSimple) {
    $view->params['edit_form'] = 'light';
}else {
    $view->params['edit_form'] = 'null';
}

if ($droitLire) {
    echo $view->renderHtml();
    echo BimpRender::renderAjaxModal('page_modal');
} else
    echo BimpRender::renderAlerts('Vous n\'avez pas la permission de voir cette page');
