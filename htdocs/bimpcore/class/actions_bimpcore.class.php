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
//            if(!isset($object->array_options) || count($object->array_options) == 0)
                $object->fetch_optionals();    
            $serialisable = $object->array_options['options_serialisable'];
            $hookmanager->resPrint = Bimp_Product::getStockIconStatic($object->id, null, $serialisable); // $id_entrepôt facultatif, peut être null.
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

//        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Societe');
//        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Client');
//        BimpTools::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Fournisseur');
//        BimpTools::changeDolObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'societe');
        
        
        BimpObject::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Client');
        BimpObject::changeBimpObjectId($parameters['soc_origin'], $parameters['soc_dest'], 'bimpcore', 'Bimp_Fournisseur-');
        
        $soc1 = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $parameters['soc_origin']);
        $soc2 = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $parameters['soc_dest']);
        global $user;
        $text = "Societe ".$soc1->getData('nom').' '.$soc1->getData('code_client').' ('.$soc1->getData('code_compta').' || '.$soc1->getData('code_compta_fournisseur').') fusionné dans '.$soc2->getData('nom').' '.$soc2->getData('code_client').' ('.$soc2->getData('code_compta').' || '.$soc2->getData('code_compta_fournisseur').') par '.$user->getNomUrl();
        
        BimpTools::mailGrouper('tommy@bimp.fr, comptaolys@bimp.fr', null, $text);
//        mailSyn2('Fusion tier', 'tommy@bimp.fr, comptaolys@bimp.fr, a.delauzun@bimp.fr', null, $text);

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
//                    
//                    // Inclusion notifications
//                    $notification = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNotification');
//                    $config_notification = $notification->getList();
//                    
//                    $html .= 'var notificationActive = {';
//
//                    foreach($config_notification as $cn) {
//                        $html .= $cn['nom'] . ": {";
//                        $html .= "module: '" . $cn['module'] . "',";
//                        $html .= "class: '" . $cn['class'] . "' ,";
//                        $html .= "method: '" . $cn['method'] . "' ,";
//                        $html .= "obj: null},";
//                    }
//                    $html .= '};';
//                    // Fin inclusion notifications

                    $html .= '</script>';
                    $html .= BimpCore::displayHeaderFiles(false);
                } else {
                    checkBimpCoreVersion();
                    global $main_controller;
                    if (is_a($main_controller, 'BimpController')) {
                        $html .= $main_controller->displayHeaderFiles(false);
                    }
                }
                
//                if (is_object($langs) && (!class_exists('BimpTools') || BimpTools::getContext() != "public")) {
//                    $this->bimp_fixe_tabs = new FixeTabs();                
//                    if($this->bimp_fixe_tabs->can("view")){
//                        $this->bimp_fixe_tabs->init();
//                        $html .= $this->bimp_fixe_tabs->displayHead(false);
//                    }
//                }
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
//            if (is_object($this->bimp_fixe_tabs) && is_a($this->bimp_fixe_tabs, 'FixeTabs')) {              
//                if($this->bimp_fixe_tabs->can("view"))
//                    $html .= $this->bimp_fixe_tabs->render();
//            }
        }
        echo $html;//bug bizarre
        $html = '';
        
        $this->resprints = $html;
        return 0;
    }
    
    
    function addSearchEntry($parameters, &$object, &$action, $hookmanager) {
        global $langs;
	$hookmanager->resArray['searchintolivraison']=array('position' => 49, 'img'=>"generic", 'text'=>img_object("Expédition", "generic") ." ". $langs->trans("Expédition"), 'url'=>DOL_URL_ROOT.'/bimpcommercial/index.php?fc=commandes&search=1&object=shipment&sall='.GETPOST('q'), 'label'=>'Expédition');
	$hookmanager->resArray['searchintoreception']=array('position' => 50, 'img'=>"generic", 'text'=>img_object("Reception", "generic") ." ". $langs->trans("Reception"), 'url'=>DOL_URL_ROOT.'/bimpcommercial/index.php?fc=commandesFourn&search=1&object=reception&sall='.GETPOST('q'), 'label'=>'Reception');
        return 0;
    }
}

//require_once(DOL_DOCUMENT_ROOT."/bimptheme/main.inc.php");
