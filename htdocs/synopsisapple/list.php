<?php

require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . '/synopsischrono/class/chrono.class.php');
require_once(DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php');
llxHeader();


$req = 'SELECT * FROM `' . MAIN_DB_PREFIX . 'synopsischrono_chrono_101` WHERE `N__Serie` LIKE "%' . $_REQUEST['filtre'] . '%" LIMIT 0,10;';
$sql = $db->query($req);
$tabMat = array();
$obj = new Chrono($db);
$obj2 = new Chrono($db);
$contratdet = new ContratLigne($db);
$contrat = new Contrat($db);
while ($ln = $db->fetch_object($sql)) {
    $obj->fetch($ln->id);
    $tabMat[$ln->id] = array("nomUrl" => $obj->getNomUrl(1));
    $tabEl = getElementElement(null, "productCli", null, $ln->id);
    $tabEl2 = array();
    foreach ($tabEl as $el) {
        $tabEl2[strtoupper($el['ts'])][] = $el['s'];
    }
    echo "
  <fieldset>
    <legend>Matérielle</legend>";
    echo $tabMat[$ln->id]['nomUrl'] . "<br/>";



    $type = "SAV";
    echo "
  <fieldset>
    <legend>" . $type . "</legend>";
    if (isset($tabEl2[$type])) {
        foreach ($tabEl2[$type] as $el) {
            $obj2->fetch($el);
            echo $obj2->getNomUrl(1);
            echo "<br/>";
        }
    }

    $type = "APPEL";

    echo "
    </fieldset>";
    echo "
  <fieldset>
    <legend>" . $type . "</legend>";
    if (isset($tabEl2[$type])) {
        foreach ($tabEl2[$type] as $el) {
            $obj2->fetch($el);
            echo $obj2->getNomUrl(1);
            echo "<br/>";
        }
    }

    $type = "Prise en charge";

    echo "
    </fieldset>";
    echo "
  <fieldset>
    <legend>" . $type . "</legend>";
    if (isset($tabEl2[$type])) {
        foreach ($tabEl2[$type] as $el) {
            $obj2->fetch($el);
            echo $obj2->getNomUrl(1);
            echo "<br/>";
        }
    }



    $type = "CONTRADET";
    echo "
    </fieldset>
  <fieldset>
    <legend>" . $type . "</legend>";
    if (isset($tabEl2[$type])) {
        foreach ($tabEl2[$type] as $el) {
            $contratdet->fetch($el);
            $contrat->fetch($contratdet->fk_contrat);
            echo $contrat->getNomUrl(1);
            echo "<br/>";
        }
    }

    echo "
    </fieldset>
    </fieldset>";
}





llxFooter();
?>