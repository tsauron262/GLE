<?php

if (!defined('BIMP_LIB')) {
    define('BIMP_LIB', 1);

    if (!defined('MOD_DEV')) {
        define('MOD_DEV', 0);
    }
    if(!MOD_DEV){
        global $dolibarr_main_prod;
        $dolibarr_main_prod = 1;
    }
    if (!defined('ID_ERP')) {
        define('ID_ERP', 1);
    }
    if (!defined('PATH_TMP')) {
        define('PATH_TMP', DOL_DATA_ROOT);
    }

    global $bimp_start_time;
    $bimp_start_time = round(microtime(1), 4);

    ini_set('display_errors', 0);

    $dir = __DIR__ . '/classes/';

    require_once __DIR__ . '/libs/spyc/Spyc.php';

    require_once $dir . 'Objects.php';
    require_once $dir . 'BimpLog.php';
    require_once $dir . 'BimpDb.php';
    require_once $dir . 'BimpValidate.php';
    require_once $dir . 'BimpCache.php';
    require_once $dir . 'BimpTools.php';
    require_once $dir . 'BimpDocumentation.php';
    require_once $dir . 'BimpConfig.php';
    require_once $dir . 'BimpDebug.php';
    require_once $dir . 'BimpForm.php';
    require_once $dir . 'BimpInput.php';
    require_once $dir . 'BimpRender.php';
    require_once $dir . 'BimpConfigDefinitions.php';
    require_once $dir . 'BimpComponent.php';

    require_once $dir . 'components/BC_Field.php';
    require_once $dir . 'components/BC_Input.php';
    require_once $dir . 'components/BC_Display.php';
    require_once $dir . 'components/BC_Search.php';
    require_once $dir . 'components/BC_Card.php';
    require_once $dir . 'components/BC_Panel.php';
    require_once $dir . 'components/BC_Filter.php';
    require_once $dir . 'components/BC_FiltersPanel.php';
    require_once $dir . 'components/BC_List.php';
    require_once $dir . 'components/BC_ListTable.php';
    require_once $dir . 'components/BC_ListViews.php';
    require_once $dir . 'components/BC_ListCustom.php';
    require_once $dir . 'components/BC_Dispatcher.php';
    require_once $dir . 'components/BC_StatsList.php';
    require_once $dir . 'components/BC_FieldsTable.php';
    require_once $dir . 'components/BC_Form.php';
    require_once $dir . 'components/BC_View.php';
    require_once $dir . 'components/BC_Input.php';
    require_once $dir . 'components/BC_Page.php';
    require_once $dir . 'components/BC_Graph.php';

//    require_once __DIR__ . '/components/BCV2_Lib.php';

    require_once $dir . 'BimpStruct.php';
    require_once $dir . 'BimpAssociation.php';
    require_once $dir . 'BimpObject.php';
    require_once $dir . 'BimpCollection.php';
    require_once $dir . 'BimpStats.php';
    require_once $dir . 'BimpCore.php';
    require_once $dir . 'FixeTabs.php';
    require_once $dir . 'BimpController.php';
    require_once $dir . 'BimpPublicController.php';
    require_once $dir . 'BimpMailCore.php';
	require_once $dir . 'BimpUserMsg.php';
    require_once $dir . 'BimpModuleConf.php';
    require_once $dir . 'BimpLayout.php';


    require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';

    BimpCore::setMaxExecutionTime(600);
    BimpCore::setMemoryLimit(1024);

    BimpObject::loadClass('bimpcore', 'Bimp_Log');
    BimpObject::loadClass('bimpcore', 'BimpNote');
    BimpObject::loadClass('bimpcore', 'BimpObjectLog');
    BimpObject::loadClass('bimpcore', 'Bimp_User');

    if (BimpCore::getExtendsEntity() == '' && defined('PATH_EXTENDS')) {
        if (preg_match('/^.*\/([a-zA-Z0-1\-_]+)\/?$/', PATH_EXTENDS, $matches)) {
            if ($matches[1]) {
                define('BIMP_EXTENDS_ENTITY', $matches[1]);

                $date_email = BimpCore::getConf('obsolete_extends_notif_date_send', '');
                if (!$date_email || $date_email < date('Y-m-d')) {
                    $msg = 'Dans conf, constante PATH_EXTENDS à remplacer par BIMP_EXTENDS_ENTITY avec la valeur "' . BIMP_EXTENDS_ENTITY . '"';
                    $msg .= '<br/><br/>';
                    $msg .= 'ERP: <b>' . DOL_URL_ROOT . '</b>';
					$code = 'PATH_EXTENDS_a_modifier';
					$sujet = 'PATH_ENTENDS à modifier';
					BimpUserMsg::envoiMsg($code, $sujet, $msg);
                    BimpCore::setConf('obsolete_extends_notif_date_send', date('Y-m-d'));
                }
            }
        }
    }

    if (BimpCore::getVersion()) {
        $dir_version = DOL_DOCUMENT_ROOT . '/bimpcore/extends/versions/' . BimpCore::getVersion() . '/';
        if (file_exists($dir_version . 'classes/BimpMail.php')) {
            require_once $dir_version . 'classes/BimpMail.php';
        }
    }

    if (BimpCore::getExtendsEntity() != '') {
        $dir_entity = DOL_DOCUMENT_ROOT . '/bimpcore/extends/entities/' . BimpCore::getExtendsEntity() . '/';
        if (file_exists($dir_entity . 'classes/BimpMail.php')) {
            require_once $dir_entity . 'classes/BimpMail.php';
        }
    }

    if (!class_exists('BimpMail')) {
        require_once $dir . 'BimpMail.php';
    }

//    BimpConfig::initCacheServeur();
}

function hookDebutFiche()
{
    BimpObject::loadClass('bimpcore', 'BimpAlert');
    echo BimpAlert::getMsgs();
}
