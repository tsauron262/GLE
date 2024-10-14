<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/bimpcore/components/BCV2_Lib.php';
echo BC_V2\BC_Form::render(array(
    'title'                => 'HOLA',
    'icon'                 => 'fas_check',
    'header_icons'         => array(
        array(
            'label'    => 'TEST',
            'icon'     => 'fas_check',
            'onclick'  => 'alert(\'OK !!\')',
            'disabled' => 1,
            'popover'  => 'Hello !!'
        )
    ),
    'footer_extra_content' => 'TEST'
));

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
