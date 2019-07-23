<?php


class ActionsBimpcore
{
    var $bimp_fixe_tabs = null;



    function getNomUrl($parameters, &$object, &$action, $hookmanager)
    {
        require_once(DOL_DOCUMENT_ROOT."/bimpcore/Bimp_Lib.php");
        global $langs;

        if ((is_a($object, "product") && $object->type == 0) || is_a($object, 'Bimp_Product')) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
            $hookmanager->resPrint = Bimp_Product::getStockIconStatic($object->id); // $id_entrepôt facultatif, peut être null.
        }

        return 0;
    }

    function replaceThirdparty($parameters, &$object, &$action, $hookmanager)
    {
        ini_set('display_errors', 1);

//        global $db;
//        $db->query("UPDATE ".MAIN_DB_PREFIX."bs_sav SET id_client = ".$parameters['soc_dest']." WHERE id_client = ".$parameters['soc_origin']);
        
        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        }

        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Societe');
        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Client');
        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Fournisseur');
        BimpTools::changeDolObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'societe');

        return 0;
    }
    
    function setContentSecurityPolicy($parameters, &$object, &$action, $hookmanager){
        global $conf, $user, $langs;
        $html = '';
         if ($user->id > 0 && stripos($_SERVER['PHP_SELF'], "synopsistools/agenda/vue.php") < 1) {
                if (!defined('BIMP_CONTROLLER_INIT')) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
                    checkBimpCoreVersion();
                    $html .= '<script type="text/javascript">';
                    $html .= 'if (!dol_url_root) {';
                    $html .= 'var dol_url_root = \'' . DOL_URL_ROOT . '\';}';
                    $html .= 'var ajaxRequestsUrl = \'' . DOL_URL_ROOT . '/bimpcore/index.php\';';
                    $html .= '</script>';
                    $html .= BimpCore::displayHeaderFiles(false);
                } else {
                    checkBimpCoreVersion();
                    global $main_controller;
                    if (is_a($main_controller, 'BimpController')) {
                        $html .= $main_controller->displayHeaderFiles(false);
                    }
                }
                
                if (is_object($langs) && (!class_exists('BimpTools') || BimpTools::getContext() != "public")) {
//                    $this->bimp_fixe_tabs = new FixeTabs();                
//                    if($this->bimp_fixe_tabs->can("view")){
//                        $this->bimp_fixe_tabs->init();
//                        $html .= $this->bimp_fixe_tabs->displayHead(false);
//                    }
                }
            }
            
            $conf->global->MAIN_HTML_HEADER .= $html;
    }
    
    function printLeftBlock($parameters, &$object, &$action, $hookmanager){
        require_once(DOL_DOCUMENT_ROOT."/bimpcore/Bimp_Lib.php");
        $html = '';
        if (defined('BIMP_LIB')) {
            if (!defined('BIMP_CONTROLLER_INIT')) {
                $html .= BimpRender::renderAjaxModal('page_modal');
            }
            if (is_object($this->bimp_fixe_tabs) && is_a($this->bimp_fixe_tabs, 'FixeTabs')) {              
                if($this->bimp_fixe_tabs->can("view"))
                    $html .= $this->bimp_fixe_tabs->render();
            }
        }
        echo $html;//bug bizarre
        $html = '';
        
        $this->resprints = $html;
        return 0;
    }
}

//require_once(DOL_DOCUMENT_ROOT."/bimptheme/main.inc.php");
