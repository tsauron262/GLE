<?php

require("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';



$id = GETPOST('id', 'int');
if($id < 1)
    $id = $user->id;

$object = new User($db);
$object->fetch($id);
$object->getrights('user');


llxHeader();


$head = user_prepare_head($object);

dol_fiche_head($head, 'formSimple', 'Essentielles', -1, 'user');

if($user->id == $id){//On est dans le form de l'utilisateur
    $droitLire = 1;
    $droitModifSimple = 1;
    $droitModif = $object->rights->user->self->creer;
}
else{
    $droitLire = $object->rights->user->user->lire;
    $droitModifSimple = $object->rights->user->user->creer;
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




define('BIMP_NEW', 1);
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
BimpCore::displayHeaderFiles();

$user = BimpObject::getInstance('bimpcore', 'Bimp_User', $id);
$view = new BC_View($user, 'default');

$full_rights = false;
if ($droitModif) {
    $view->params['edit_form'] = 'default';
} elseif ($droitModifSimple) {
    $view->params['edit_form'] = 'light';
}else {
    $view->params['edit_form'] = 'null';
}

if($droitLire) {
    echo $view->renderHtml();
    echo BimpRender::renderAjaxModal('page_modal');
}
else
    echo "Vous n'avez pas les droits";
