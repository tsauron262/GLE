<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CSV PAR ENTREPOT', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$type_place = (int) BimpTools::getValue('type_place');
BimpObject::loadClass('bimpequipment', 'BE_Place');

if (!$type_place) {
    echo 'TYPE EMPLACEMENT: <br/><br/>';
    foreach (BE_Place::$types as $type => $label) {
        if (!in_array($type, BE_Place::$entrepot_types)) {
            continue;
        }
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/generate_csv_by_entrepot.php?type_place=' . $type . '">';
        echo $label;
        echo '</a><br/>';
    }
} else {
    echo 'DEBUT <br/><br/>';

    $type_label = strtolower(BimpTools::cleanStringForUrl(BE_Place::$types[$type_place]));

    $dir = DOL_DATA_ROOT . '/bimpcore/lists_csv/products_by_entrepot/' . $type_label . '/' . date('Y-m-d');

    if (!file_exists($dir)) {
        $error = BimpTools::makeDirectories(array(
                    'bimpcore' => array(
                        'lists_csv' => array(
                            'products_by_entrepot' => array(
                                $type_label => date('Y-m-d')
                            )
                        ),
                    )
                        ), DOL_DATA_ROOT);
        if ($error || !file_exists($dir)) {
            echo BimpRender::renderAlerts('Echec de la création du dossier "' . $dir . '"' . ($error ? ': ' . $error : ''));
            echo '</body></html>';
            exit;
        }
    }

    $entrepots = BimpCache::getEntrepotsArray();
    $instance = BimpObject::getInstance('bimpequipment', 'BE_ProductImmos');

    $list = new BC_ListTable($instance, 'immos');
    $list->addFieldFilterValue('rowid', 0);

    $header = $list->renderCsvContent(';', array());

    $_POST['filters_panel_values'] = array(
        'fields' => array()
    );

    $entrepot = new Entrepot($db);
    foreach ($entrepots as $id_entrepot => $ent_label) {
        $_POST['filters_panel_values']['fields'] = array(
            'place_type'        => array(
                'values' => array($type_place)
            ),
            'place_id_entrepot' => array(
                'values' => array($id_entrepot)
            )
        );

        
        
        $list = new BC_ListTable($instance, 'immos');
        $content = $list->renderCsvContent(';', array(), false);

        if (!empty($content)) {

            echo 'CONTENT: <br/>';
            $entrepot->fetch((int) $id_entrepot);

            $file = $entrepot->ref . '_' . date('Y-m-d') . '.csv';

            error_reporting(E_ALL);
            if (file_put_contents($dir . '/' . $file, $header . $content) === false) {
                echo '<span class="danger">Echec de la création du fichier "' . $dir . '/' . $file . '" (Entrepôt ' . $entrepot->ref . ')</span><br/>';
            }
            error_reporting(E_ERROR);
        }
        
        BimpCache::$cache = array();
    }

    $zip_name = $type_label . '_' . date('Y-m-d') . '.zip';

    $zip = new ZipArchive();
    if (!$zip->open($dir . '/' . $zip_name, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        echo BimpRender::renderAlerts('Echec de la créatio du zip ""');
    } else {
        echo 'CREA ZIP OK <br/>';
        foreach (scandir($dir) as $f) {
            if (preg_match('/^.+\.csv$/', $f)) {
                $zip->addFile($dir . '/' . $f, $f);
            }
        }
        $zip->close();

        echo '<script type="text/javascript">';
        $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('lists_csv/products_by_entrepot/' . $type_label . '/' . date('Y-m-d') . '/' . $zip_name);
        echo 'window.open(\'' . $url . '\')';
        echo '</script>';
    }

    echo '<br/>FIN';
}

echo '</body></html>';

//llxFooter();

