<?php
//error_reporting(E_ALL);
//error_reporting(E_ERROR);
//ini_set('display_errors', 1);

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT .'/includes/nusoap/lib/nusoap.php';

require_once ( 'gsxDatas.class.php' );

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'newSerial':
            if (isset($_GET['serial'])) {
                $datas = new gsxDatas($_GET['serial']);
                echo $datas->getLookupHtml();
            } else {
                echo '<p class="error">Aucun numéro de série fournit</p>' . "\n";
            }
            break;

            case 'searchParts':
                if (isset($_GET['serial'])) {
                $datas = new gsxDatas($_GET['serial']);
                echo $datas->getPartsHtml(isset($_GET['filter'])?$_GET['filter']:null, isset($_GET['search'])?$_GET['search']:null);
            } else {
                echo '<p class="error">Aucun numéro de série fournit</p>' . "\n";
            }
                break;
    }
    die('');
}
?>
