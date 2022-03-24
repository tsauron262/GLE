<?php

require_once('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

llxHeader();

$file = $_GET['name'];

$errors = array();


echo '<a href="https://fr.wikipedia.org/wiki/BBCode#Listes_de_balises">Doc BBCode</a><br/><br/>';

$BimpDocumentation = new BimpDocumentation('doc', $file);


if (isset($_POST['html'])) {
    $BimpDocumentation->saveDoc($file, $_POST['html']);
    $_GET['action'] = 'read';
}

if ($file == '')
    $errors[] = 'Pas de doc spécifié';
elseif ($_GET['action'] == 'edit') {
    echo $BimpDocumentation->getEditFormDoc();
} else {
    echo $BimpDocumentation->displayDoc();
}


if (count($BimpDocumentation->errors))
    echo BimpRender::renderAlerts($BimpDocumentation->errors);

?>
