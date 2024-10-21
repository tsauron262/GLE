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

$params = array(
    'title'                => 'Hello',
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
    'footer_extra_content' => '',
    'elements'             => array(
        array(
            'label' => 'text',
//            'input' => array(
//                'type' => 'text'
//            )
            'help'  => 'TEST TEST TEST'
        ),
        array(
            'label' => 'password',
            'input' => array(
                'type' => 'password',
            )
        ),
        array(
            'label' => 'number',
            'input' => array(
                'type' => 'number'
            )
        ),
        array(
            'label' => 'qty',
            'input' => array(
                'type' => 'qty'
            )
        ),
        array(
            'label' => 'textarea',
            'input' => array(
                'type' => 'textarea'
            )
        ),
        array(
            'label' => 'html',
            'input' => array(
                'type' => 'html'
            )
        ),
        array(
            'label' => 'datetime',
            'input' => array(
                'type' => 'datetime'
            )
        ),
        array(
            'label' => 'date',
            'input' => array(
                'type' => 'date'
            )
        ),
        array(
            'label' => 'time',
            'input' => array(
                'type' => 'time'
            )
        ),
        array(
            'label' => 'timer',
            'input' => array(
                'type' => 'timer'
            )
        ),
        array(
            'label' => 'select',
            'input' => array(
                'type' => 'select'
            )
        ),
        array(
            'label' => 'toggle',
            'input' => array(
                'type' => 'toggle'
            )
        ),
        array(
            'label' => 'check_list',
            'input' => array(
                'type' => 'check_list'
            )
        ),
        array(
            'label' => 'select_user',
            'input' => array(
                'type' => 'select_user'
            )
        ),
        array(
            'label' => 'ziptown',
            'input' => array(
                'type' => 'ziptown'
            )
        ),
        array(
            'label' => 'search_object',
            'input' => array(
                'type' => 'search_object'
            )
        ),
        array(
            'label' => 'object_filters',
            'input' => array(
                'type' => 'object_filters'
            )
        ),
        array(
            'label' => 'signature_pad',
            'input' => array(
                'type' => 'signature_pad'
            )
        ),
        array(
            'label' => 'drop_files',
            'input' => array(
                'type' => 'drop_files'
            )
        ),
        array(
            'label' => 'file_upload',
            'input' => array(
                'type' => 'file_upload'
            )
        ),
        array(
            'label' => 'hidden',
            'input' => array(
                'type' => 'hidden'
            )
        ),
        array(
            'label' => 'custom',
            'input' => array(
                'type' => 'custom'
            )
        )
    )
);

$html = BC_V2\BC_Form::render($params);

echo '---- Composant : ----- <br/><br/>';
echo $html;

//echo '<br/><br/>----- HTML : ----- <br/><br/>';
//echo htmlentities($html);

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
