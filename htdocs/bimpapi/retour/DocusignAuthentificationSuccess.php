<?php

require_once("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

top_htmlhead('', 'Authentification DocuSign', 0, 0, array(), array());

echo '<body>';
BimpCore::displayHeaderFiles();

$error = $warning = array();

$code = BimpTools::getValue('code', '');
if (!$code) {
    $errors[] = 'Code absent';
} else {
    if (BimpTools::isSubmit('mode_dev') && 0 /*o peut pas test sinon*/) {
        echo 'Code : "' . $code . '"<br/><br/>';
    } else {
        if (!isset($_SESSION['id_user_docusign'])) {
            $errors[] = 'ID Utilisateur DocuSign absent - impossible de finaliser l\'authentification DocuSign';
        } else {
            $userAcompte = BimpCache::getBimpObjectInstance('bimpapi', 'API_UserAccount', $_SESSION['id_user_docusign']);

            if (!BimpObject::objectLoaded($userAcompte)) {
                $errors[] = 'Aucun compte utilisateur trouvé pour l\'ID ' . $_SESSION['id_user_docusign'];
            } else {
                unset($_SESSION['id_user_docusign']);
                $save_errors = $userAcompte->saveToken('code', $_GET['code']);

                if (count($save_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($save_errors, 'Echec de l\'enregistrement du token');
                } else {
                    $userAcompte->connect($warning);
                }
            }
        }
    }
}

if (!count($error)) {
    echo BimpRender::renderAlerts('Authentification réussi, veuillez réitérer votre requête sur l\'ERP', 'success');
} else {
    echo BimpRender::renderAlerts($errors);
}

if (count($warning)) {
    echo BimpRender::renderAlerts($warnings, 'warning');
}

echo '</body></html>';
