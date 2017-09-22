<?php

require_once '../main.inc.php';
require_once __DIR__ . '/BDS_Lib.php';
require_once(DOL_DOCUMENT_ROOT . "/bimpdatasync/core/lib/bimpdatasync.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/bimpdatasync/views/render.php");


$jsFiles = array(
    '/bimpdatasync/views/js/functions.js',
    '/bimpdatasync/views/js/ajax.js'
);

llxHeader('', '', '', false, false, false, $jsFiles);

echo '<link type="text/css" rel="stylesheet" href="./views/css/font-awesome.css"/>';
echo '<link type="text/css" rel="stylesheet" href="./views/css/styles.css"/>';

$id_process = BDS_Tools::getValue('id_process', 0);

if (!$id_process) {
    print load_fiche_titre('Gestion des imports, exports et synchronisations des données', '', 'title_generic.png');

    echo BDSProcess::renderFormAndList();
} else {
    $process = new BDSProcess();
    $process->fetch($id_process);

    if (is_null($process->id) || !$process->id) {
        echo '<p class="alert-danger">Le processus d\'ID ' . $id_process . ' n\'a pas été trouvé</p>';
        llxFooter();
        exit;
    }

    print load_fiche_titre($process->title, '', 'title_generic.png');

    global $db;
    $bdb = new BimpDb($db);

    $head = process_prepare_head($process);
    $tab = BDS_Tools::getValue('tab', 'general');

    dol_fiche_head($head, $tab, 'Processus');

    switch ($tab) {
        case 'general':
            echo $process->renderForm();
            break;

        case 'parameters':
            echo $process->renderObjectsList('parameters');
            break;

        case 'options':
            echo $process->renderObjectFormAndList('options');
            break;

        case 'matching':
            echo $process->renderObjectFormAndList('matching');
            break;
        
        case 'triggers':
            echo $process->renderObjectsList('trigger_action');
            break;

        case 'operations':
            echo $process->renderObjectFormAndList('operations');
            break;
        
        case 'crons':
            echo $process->renderObjectFormAndList('crons');
            break;
    }
}

llxFooter();
