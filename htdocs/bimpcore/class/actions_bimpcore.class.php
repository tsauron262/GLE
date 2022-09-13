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

                if (0) {
                    $layout = new BimpLayout();
                }

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

    function bimpcoreInit($parameters, &$object, &$action, $hookmanager)
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

        if (!BimpCore::$is_init) {
            BimpCore::init();
        }

        initBimpHeader();
        $this->incrementNbReq();
    }

    function initBimpLayout($parameters, &$object, &$action, $hookmanager)
    {
        BimpCore::initLayout();

        global $main_controller;

        if (is_a($main_controller, 'BimpController')) {
            $main_controller->initLayout();
        }
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
//        return 0;
    }

    function addSearchEntry($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        $hookmanager->resArray['searchintolivraison'] = array('position' => 49, 'img' => "generic", 'text' => img_object("Expédition", "generic") . " " . $langs->trans("Expédition"), 'url' => DOL_URL_ROOT . '/bimpcommercial/index.php?fc=commandes&search=1&object=shipment&sall=' . GETPOST('q'), 'label' => 'Expédition');
        $hookmanager->resArray['searchintoreception'] = array('position' => 50, 'img' => "generic", 'text' => img_object("Reception", "generic") . " " . $langs->trans("Reception"), 'url' => DOL_URL_ROOT . '/bimpcommercial/index.php?fc=commandesFourn&search=1&object=reception&sall=' . GETPOST('q'), 'label' => 'Reception');
        return 0;
    }
}
