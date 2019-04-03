<?php
global $connected_client;
require_once DOL_DOCUMENT_ROOT . '/bimpinterfaceclient/class/object.class.php';
$interface_contrat = new object($db);
$liste_contrat = $interface_contrat->getContratList($connected_client, 'contrat');
if ($liste_contrat) {
    //print_r($liste_contrat);
    print $interface_contrat->renderListContrat($liste_contrat);
    
} else {
    
}
?>
