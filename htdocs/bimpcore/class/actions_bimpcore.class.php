<?php

function initBimpHeader()
{
    if (!defined('NOREQUIREHTML')) {
        if (!function_exists('llxHeader')) {

            function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '')
            {
                global $hookmanager;

                // Création et initialisation du BimpLayout: 
                $layout = BimpLayout::getInstance();

                $layout->extra_head .= $head;
                $layout->body_classes[] = $morecssonbody;
                $layout->no_js = $disablejs;
                $layout->no_head = $disablehead;

                if ($title) {
                    $layout->page_title = $title;
                }
                if ($help_url) {
                    $layout->help_url = $help_url;
                }
                if ($target) {
                    $layout->menu_target = $target;
                }
                if ($morequerystring) {
                    $layout->menu_morequerystring = $morequerystring;
                }
                if ($replacemainareaby) {
                    $layout->main_area_html = $replacemainareaby;
                }

                if (!empty($arrayofjs)) {
                    foreach ($arrayofjs as $js) {
                        $layout->addJsFile($js);
                    }
                }

                if (!empty($arrayofcss)) {
                    foreach ($arrayofcss as $css) {
                        $layout->addCssFile($css);
                    }
                }

                $hookmanager->initHooks(array('initBimpLayout'));
                $hookmanager->executeHooks('initBimpLayout', array());
                $layout->begin();
            }
        } else {
            header("Refresh:0");
        }
    }
}

class ActionsBimpcore
{

    var $bimp_fixe_tabs = null;
    var $resprints = '';
    var $priority = 1;

    function bimpcoreInit($parameters, &$object, &$action, $hookmanager)
    {
        if (!defined('BIMPCORE_INIT')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

            if (!BimpCore::$is_init) {
                BimpCore::init();
            }

            initBimpHeader();
            $this->incrementNbReq();

            define('BIMPCORE_INIT', 1);
        }
        $this->resprints .= $this->getBtnRedirectHoldToNew();

        return 0;
    }
    
    function printMainArea(){
        $this->resprints = '';
        $this->resprints .= $this->getBtnRedirectHoldToNew();
        return 0;
    }
    
    function getBtnRedirectHoldToNew(){
//        return '';
        $url = $_SERVER['REQUEST_URI']; 
        if(isset($_REQUEST['facid']))
            $_REQUEST['id'] = $_REQUEST['facid'];
        if(isset($_REQUEST['socid']))
            $_REQUEST['id'] = $_REQUEST['socid'];

        if(stripos($url, '/admin/') === false && stripos($url, '/fourn/commande/dispatch.php') === false){
            if(stripos($url, '/commande/') !== false)
                    $tabObj = array("bimpcommercial", "Bimp_Commande");
            if(stripos($url, '/compta/facture/') !== false && stripos($url, 'action=create&origin=') === false)
                    $tabObj = array("bimpcommercial", "Bimp_Facture");
            if(stripos($url, '/contrat/') !== false)
                    $tabObj = array("bimpcontract", "BContract_contrat");
            if(stripos($url, '/comm/action') === false && stripos($url, '/comm/index.php') === false && stripos($url, '/comm/rem') === false && stripos($url, 'admin') === false && (stripos($url, '/comm/') !== false || stripos($url, '/societe/') !== false)){
                if(BimpTools::getValue('type', 's') == 'f')
                    $tabObj = array("bimpcore", "Bimp_Fournisseur");
                else
                    $tabObj = array("bimpcore", "Bimp_Client");
            }
            if(stripos($url, 'comm/propal') !== false)
                    $tabObj = array("bimpcommercial", "Bimp_Propal");


            if(stripos($url, '/fourn/commande/') !== false)
                    $tabObj = array("bimpcommercial", "Bimp_CommandeFourn");
            elseif(stripos($url, '/fourn/facture/') !== false)
                    $tabObj = array("bimpcommercial", "Bimp_FactureFourn", 'facid');
            elseif(stripos($url, '/fourn/') !== false)
                    $tabObj = array("bimpcore", "Bimp_Fournisseur");


            if(stripos($url, '/product/') !== false){
                if(stripos($url, '/stock/') !== false)
                    $tabObj = array("bimpcore", "Bimp_Entrepot");
                elseif(stripos($url, '/reassort.php'))
                    $tabObj = array("bimpcommercial", "Bimp_Product_Entrepot");
                else
                    $tabObj = array("bimpcore", "Bimp_Product");
            }

            if(stripos($url, '/synopsisdemandeinterv/') !== false || stripos($url, '/synopsisfichinter/') !== false)
                    $tabObj = array("bimptechnique", "BT_ficheInter");

            if(stripos($url, '/user/') !== false && !stripos($url, 'create'))
                    $tabObj = array("bimpcore", "Bimp_User");
            if(stripos($url, '/user/group/') !== false)
                    $tabObj = array("bimpcore", "Bimp_UserGroup");
        }


        if(isset($tabObj) && stripos($url, 'ajax') === false){
            $bObj = BimpObject::getInstance($tabObj[0], $tabObj[1], $_REQUEST[(isset($tabObj[2])? $tabObj[2] : 'id')]);
            return $bObj->processRedirect();
        }
    }

    function initBimpLayout($parameters, &$object, &$action, $hookmanager)
    {
        BimpCore::initLayout();

        global $main_controller;

        if (is_a($main_controller, 'BimpController')) {
            $main_controller->initLayout();
        }

        return 0;
    }

    function getNomUrl($parameters, &$object, &$action, $hookmanager)
    {
        require_once(DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php");
        global $langs;

        if ((is_a($object, "product") && $object->type == 0) || is_a($object, 'Bimp_Product')) {
            BimpObject::loadClass('bimpcore', 'Bimp_Product');
//            if(!isset($object->array_options) || count($object->array_options) == 0)
            $object->fetch_optionals();
            $serialisable = $object->array_options['options_serialisable'];
            $hookmanager->resPrintsArray['getNomUrl'] = Bimp_Product::getStockIconStatic($object->id, null, $serialisable); // $id_entrepôt facultatif, peut être null.
        }

        return 0;
    }

    function replaceThirdparty($parameters, &$object, &$action, $hookmanager)
    {
//        ini_set('display_errors', 1);

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
        $text = "Societe " . $soc1->getData('nom') . ' ' . $soc1->getData('code_client') . ' (' . $soc1->getData('code_compta') . ' || ' . $soc1->getData('code_compta_fournisseur') . ') fusionné dans ' . $soc2->getData('nom') . ' ' . $soc2->getData('code_client') . ' (' . $soc2->getData('code_compta') . ' || ' . $soc2->getData('code_compta_fournisseur') . ') par ' . $user->getNomUrl();

        BimpTools::mailGrouper('tommy@bimp.fr, comptaolys@bimp.fr', null, $text);
//        mailSyn2('Fusion tier', 'tommy@bimp.fr, comptaolys@bimp.fr, a.delauzun@bimp.fr', null, $text);

        return 0;
    }

    function incrementNbReq()
    {
        $mode_eco = true;
        if (defined('BIMP_LIB')) {
            $mode_eco = (int) BimpCore::getConf('mode_eco', 1);
        }
        if (!$mode_eco) {
            if (defined('ID_ERP'))
                $conf = 'nb_req_' . ID_ERP;
            else
                $conf = 'nb_req_' . $_SERVER['HTTP_HOST'];
            $nb = BimpCore::getConf($conf, 0);
            BimpCore::setConf($conf, $nb + 1);
        }
        
        return 0;
    }
    
    function getLoginPageOptions(&$parameters = false){
        $tabs = json_decode(BimpCore::getConf('entity_url'), true);
        if(is_array($tabs)){
            foreach($tabs as $entity => $urls){
                foreach($urls as $url){
                    if(stripos($_SERVER['HTTP_HOST'], $url) !== false ||
                            stripos($_SERVER['REQUEST_URI'], $url) !== false)
                        $parameters['entity'] = $entity;
                }
            }
        }
    }
    
    function afterLogin(&$parameters = false){
        if(BimpTools::isModuleDoliActif('MULTICOMPANY')){
            global $mc,$conf;
            $mc->dao->getEntities(false, false, true, true, true);
            if(!in_array($conf->entity, $mc->dao->entities)){
//                print_r($mc->dao->entities);die;
                if(count($mc->dao->entities) > 0)
                    $mc->switchEntity($mc->dao->entities[0]);
            }
        }
    }

    function printLeftBlock($parameters, &$object, &$action, $hookmanager)
    {
//        require_once(DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php");
//        $html = '';
//        if (defined('BIMP_LIB')) {
//            if (!defined('BIMP_CONTROLLER_INIT')) {
//                $html .= BimpController::renderBaseModals();
//            }
////            if (is_object($this->bimp_fixe_tabs) && is_a($this->bimp_fixe_tabs, 'FixeTabs')) {              
////                if($this->bimp_fixe_tabs->can("view"))
////                    $html .= $this->bimp_fixe_tabs->render();
////            }
//        }
//        echo $html; //bug bizarre
//        $html = '';
//
//        $this->resprints = $html;
        return 0;
    }

    function addSearchEntry($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        $hookmanager->resArray['searchintolivraison'] = array('position' => 49, 'img' => "generic", 'text' => img_object("Expédition", "generic") . " " . $langs->trans("Expédition"), 'url' => DOL_URL_ROOT . '/bimpcommercial/index.php?fc=commandes&search=1&object=shipment&sall=' . GETPOST('q'), 'label' => 'Expédition');
        $hookmanager->resArray['searchintoreception'] = array('position' => 50, 'img' => "generic", 'text' => img_object("Reception", "generic") . " " . $langs->trans("Reception"), 'url' => DOL_URL_ROOT . '/bimpcommercial/index.php?fc=commandesFourn&search=1&object=reception&sall=' . GETPOST('q'), 'label' => 'Reception');
        return 0;
    }
}
