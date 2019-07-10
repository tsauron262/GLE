<?php

if (!defined(DOL_DOCUMENT_ROOT)) {
    if (file_exists('../../main.inc.php'))
        require_once '../../main.inc.php';
    elseif (file_exists('../main.inc.php'))
        require_once '../main.inc.php';
}

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

global $db;
$admin = new User($db);
$admin->fetch(1);
$users = array();

$called_from_hook = defined('ID_SELECTED_FOR_SIGNATURE') and ID_SELECTED_FOR_SIGNATURE > 0;

if ($called_from_hook)
    $ids = array(ID_SELECTED_FOR_SIGNATURE);
else
    $ids = getUsers();

$user_errors = array();

$nb_user = sizeof($ids);
$cnt = 0;
foreach ($ids as $id) {
    $user = new User($db);
    $user->fetch((int) $id);
    $user->signature = getSignature($user);
    if ($user->update($admin) < 0)
        $user_errors[] = $user;
    $cnt++;
}

if (sizeof($user_errors) == 0)
    if($called_from_hook)
        setEventMessages($cnt . " Signature(s) utilisateur(s) mis à jour", array(), 'mesgs');
    else
        echo $cnt . " Signature(s) utilisateur(s) mis à jour";
else {
    $errors = 'Erreurs lors de la mise à jour de la signature de(des) utilisateur(s) avec id= ';
    foreach ($user_errors as $u) {
        $errors .= $u->id . ',';
    }
    if($called_from_hook)
        setEventMessages($errors, array(), 'errors');
    else
        echo $cnt . " Signature(s) utilisateur(s) mis à jour";
}

define('ID_SELECTED_FOR_SIGNATURE', -1);

/*
 * Functions
 */

function getUsers() {
    global $db;
    $ids = array();

    $sql = 'SELECT rowid';
    $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'user';
    $sql .= ' WHERE statut=1';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $ids[] = $obj->rowid;
        }
    }
    return $ids;
}

function getSignature($user) {
    $signature = '';
    $signature .= '<strong><span style = "color:#ff9300"><span style = "font-size:36px">Bimp</span><span style = "font-size:x-large">&nbsp;';
    $signature .= '</span></span></strong><span style = "font-size:x-large"><span style = "color:#919191">Groupe LDLC</span></span>';
    $signature .= '<div style="text-align:start"><div>&nbsp;</div><div><span style="font-size:medium"><span style="color:#000000"><span style="font-size:14px">';
    $signature .= '<span style="color:#919191">' . $user->firstname . ' ' . $user->lastname . ' | </span>&nbsp;';
    if (isset($user->job) and strlen($user->job) > 1)
        $signature .= '<span style="color:#919191">' . $user->job . ' | </span>&nbsp;';
    if (isset($user->office_phone) and strlen($user->office_phone) > 6)
        $signature .= '<span style="color:#919191">' . $user->office_phone . ' | </span>&nbsp;';
    $signature .= '<span style="color:#919191">0 812 211 211 |</span>';
    $signature .= '</span><span style="font-size:14px">';
    $signature .= '<span style="color:#919191">&nbsp;</span><a href="http://www.bimp.fr/"><span style="color:#919191">www.bimp.fr</span></a><span style="color:#919191">&nbsp;</span></span><span style="font-size:14px"><span style="color:#919191"></span></span></span></span></div>';
    $signature .= '<div>&nbsp;</div><div><span style="font-size:medium"><span style="color:#000000"><span style="color:#888888"><span style="font-size:9px">Ce message et &eacute;ventuellement les pi&egrave;ces jointes, sont exclusivement transmis &agrave; l&#39;usage de leur destinataire et leur contenu est strictement confidentiel. Une quelconque copie, retransmission, diffusion ou autre usage, ainsi que toute utilisation par des personnes physiques ou morales ou entit&eacute;s autres que le destinataire sont formellement interdits. Si vous recevez ce message par erreur, merci de le d&eacute;truire et d&#39;en avertir imm&eacute;diatement l&#39;exp&eacute;diteur. L&#39;Internet ne permettant pas d&#39;assurer l&#39;int&eacute;grit&eacute; de ce message, l&#39;exp&eacute;diteur d&eacute;cline toute responsabilit&eacute; au cas o&ugrave; il aurait &eacute;t&eacute; intercept&eacute; ou modifi&eacute; par quiconque.</span></span></span></span></div></div>';
    return $signature;
}
