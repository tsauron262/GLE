<?php
require_once('../main.inc.php');
llxHeader();
?>

<link type="text/css" rel="stylesheet" href="appleGSX.css"/>

<?php
ini_set('display_errors', 1);

global $db;

if (isset($_GET['id_repair'])) {
    $id_repair = $_GET['id_repair'];
} elseif (isset($_GET['id_chrono'])) {
    $sql = 'SELECT `rowid` FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_repair ';
    $sql .= 'WHERE `chronoId` = ' . (int) $_GET['id_chrono'] . ' LIMIT 1';
    $result = $db->query($sql);
    if ($db->num_rows($result) > 0) {
        $datas = $db->fetch_object($result);
        $id_repair = $datas->rowid;
    }
}

if (!isset($id_repair)) {
    echo 'Pas d\'ID répa trouvé';
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';
$gsxDatas = new gsxDatas('');
$gsxDatas->updateRepairTotalOrder($id_repair);

llxFooter();

