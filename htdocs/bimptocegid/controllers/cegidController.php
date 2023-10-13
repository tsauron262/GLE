<?php

require_once DOL_DOCUMENT_ROOT . 'bimptocegid/class/export.class.php';
require_once DOL_DOCUMENT_ROOT . 'bimptocegid/class/cron.class.php';
require_once DOL_DOCUMENT_ROOT . 'bimptocegid/class/controle.class.php';
require_once DOL_DOCUMENT_ROOT . 'bimptocegid/objects/TRA.class.php';
require_once DOL_DOCUMENT_ROOT . 'bimpcore/classes/BimpDocumentation.php';

class cegidController extends BimpController {
    
    protected $version_tra;
    protected $extendsEntity;
    protected $local_path;
    protected $traClass;
    protected $exportClass;

    public function renderHeader() {
        
        global $db;
        
        $this->version_tra = BimpCore::getConf('version_tra', null, "bimptocegid");
        $this->extendsEntity = BimpCore::getExtendsEntity();
        $this->traClass = BimpCache::getBimpObjectInstance('bimptocegid', 'TRA');
        $this->exportClass = new export($db);
        
        $doc = new BimpDocumentation('doc', 'compta');
        
        return 
        '<div id="bimptocegid__header" class="object_header container-fluid" style="height: auto;">'
            . '<div class="row">'
                . '<div class="col-lg-6 col-sm-8 col-xs-12">'
                    . '<div style="display: inline-block">'
                        . '<div class="object_header_title">'
                            . '<h1>'
                                . '<i class="fas fa5-file-invoice-dollar iconLeft"></i>BimpToCegid <br />'
                            . '</h1>'
                        . '</div>'
                    . '</div>'
                . '</div>'
            . '</div>'
            . '<div class="row header_bottom">'.$doc->renderBtn('compta').'</div>';
        $html .= '</div>';
    }
    
    private function renderObjectList($instance, $title) {
        
        $list = new BC_ListTable($instance, 'default', 1, null, $title);
        $list->addFieldFilterValue('exported', 1);
        $list->setParam('open', 0);
        
        return $list->renderHtml();
        
    }
    
    private function getNbExported($table, $primary) {
        
        return $this->traClass->db->getCount($table, 'exported = 1', $primary);
        
    }
    
    public function renderCheckTab() {
        
        $html = '';
        
        $tabs[] = array(
            'id'            => 'check_paiement',
            'title'         => 'Paiements',
            'ajax'          => 1,
            'ajax_callback' => $this->traClass->getJsLoadCustomContent('renderNavTabCheck', '$(\'#check_paiement .nav_tab_ajax_result\')', array('data' => Array('paiement')), array())
        );
        
        $html .= BimpRender::renderNavTabs($tabs, 'card_view');
        
        return $html;
        
    }
    
    public function renderCheckTitle() {
        return 'Check ' . BimpRender::renderIcon('check');
    }
    
    public function renderPaiementsTab() {
        $html = '';
        
        $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement');
        
        $html .= $this->getExportedFilesArray('paiements');
        $html .= $this->getExportedFilesArray('deplacementpaiements');
        $html .= $this->getExportedFilesArray('payni');
        $html .= $this->getExportedFilesArray('ip');

        $html .= $this->renderObjectList($instance, 'Liste des paiements exportés ('.$this->getNbExported('paiement', 'rowid').')');
        
        return $html;
    }
    
    public function renderVentesTab() {
        $html = '';
        
        $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture');
        
        $html .= $this->getExportedFilesArray('ventes');
        
        $html .= $this->renderObjectList($instance, 'Liste des factures exportées ('.$this->getNbExported('facture', 'rowid').')');
        
        return $html;
    }
    
    public function renderAchatsTab() {
        $html = '';
        
        $instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn');
        
        $html .= $this->getExportedFilesArray('achats');
        
        $html .= $this->renderObjectList($instance, 'Liste des factures fournisseur exportées ('.$this->getNbExported('facture_fourn', 'rowid').')');
        
        return $html;
    }
    
    public function renderTiersTab() {
        $html = '';
        
        $instance = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe');
        
        $html .= $this->getExportedFilesArray('tiers');
        
        $html .= $this->renderObjectList($instance, 'Liste des tiers exportés ('.$this->getNbExported('societe', 'rowid').')');
        
        return $html;
    }
    
    public function renderCurrentTab() {
        
        global $user;
        
        $html = '';
        
        $cron = new Cron();
        
        $html .= '<div id=\'button_edit\' >' . '<button onClick=\'transfert()\' class=\'btn btn-info\' ><i class=\'fas fa5-paper-plane\' ></i> Envoyer les fichiers manuellement</button>';
                
        $html .= $this->getExportedFilesArray();

        return $html;
    }
    
    public function renderConfigTab() {
        $html .= '';

        $tabs[] = array(
            'id'            => 'comptes_achats',
            'title'         => 'Comptes 6',
            'ajax'          => 1,
            'ajax_callback' => $this->traClass->getJsLoadCustomContent('renderNavTabAchats', '$(\'#comptes_achats .nav_tab_ajax_result\')', array(), array())
        );
        
        $tabs[] = array(
            'id'            => 'comptes_ventes',
            'title'         => 'Comptes 7',
            'ajax'          => 1,
            'ajax_callback' => $this->traClass->getJsLoadCustomContent('renderNavTabVentes', '$(\'#comptes_ventes .nav_tab_ajax_result\')', array(), array())
        );
        
        return BimpRender::renderNavTabs($tabs, 'card_view');
    }
    
    public function renderSearchTab() {
        $html = '';
        $form = new BC_Form($this->traClass, null, 'search');
        $html .= $form->renderHtml();
        
        return $html;
    }
    
    private function displayStructureState($file) {
        $checkStructure = controle::tra($file, file($file), '');
        $html = '';
        $errors = Array();
        
        if($checkStructure['header'] != '')
            $errors[] = $checkStructure['header'];
        if(count($checkStructure['alignement'])) {
            foreach($checkStructure['alignement'] as $erreur) {
                $errors[] = $erreur;
            }
        }
        
        if(count($errors) > 0) {
            $html .= '<i class=\'fas fa5-times bs-popover danger\' '.BimpRender::renderPopoverData(implode('</br >', $errors), 'top', true).' ></i>';
        } else {
            $html .= '<i class=\'fas fa5-check success \' ></i>';
        }
        
        return $html;
        
    }
    
    private function getExportedFilesArray($type = '') {
        
        $html = '';
        
        $tra = BimpCache::getBimpObjectInstance('bimptocegid', 'TRA');
        $this->local_path = PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/' . 'imported_auto' . '/';
        $open = false;
        
        if($type != '') {
            switch($type) {
                case 'tiers': $number = '0'; $typeFile = 'tiers'; break;
                case 'ventes': $number = '1'; $typeFile = 'ventes'; break;
                case 'achats': $number = '3'; $typeFile = 'achats'; break;
                case 'paiements': $number = '2'; $typeFile = 'paiements'; break;
                case 'deplacementpaiements': $number = '7'; $typeFile = 'deplacementpaiements'; break;
                case 'payni': $number = '6'; $typeFile = 'payni'; break;
                case 'ip': $number = null; $typeFile = 'ip'; break;
            }

            if($typeFile == 'ip') {
                $pattern = 'IP' . '*' . '.tra';
            } else {
                $pattern = $number . '_' . $this->extendsEntity . '_(' . strtoupper($typeFile) . ')_' . '*' . '_' . $this->version_tra . '.tra';
            }
        } else {
            $open = true;
            $this->local_path = PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/';
            $pattern = '*' . '.tra';
        }
        
        
        
        
        $headerList = [
            'name'      => 'Nom du fichier',
            'date'      => 'Date du fichier',
            'size'      => 'Taille du fichier',
            'struct'    => 'Structure',
            'buttons'   => ['label' => '', 'col_style' => 'text-align: right']
        ];
        $rows = [];
        
        $dateFichier = new DateTime();
        
        foreach(glob($this->local_path . $pattern) as $file) {
            $dateFichier->setTimestamp(fileatime($file));
            
            $controle = controle::tra($file, file($file), '', true);
            
            $lineInBold = $controle['alignement'];
            if($controle['header'] > 0)
                $lineInBold[] = $controle['header'];

            $buttons = '';
            $buttons .= BimpRender::renderRowButton('Voir le contenue du fichier', 'fas_eye', $tra->getJsLoadModalCustomContent('displayTraFile', basename($file), Array('file' => $file, 'lineInBold' => $lineInBold)));
            if($typeFile == 'tiers') {
                $buttons .= BimpRender::renderRowButton('Voir la liste des tiers du fichier', 'fas_user', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des tiers du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_Societe', 'module' => 'bimpcore', 'startChar' => 6, 'strlen' => 17)));
            }
            if($typeFile == 'ventes') {
                $buttons .= BimpRender::renderRowButton('Voir la liste des tiers du fichier', 'fas_user', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des tiers du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_Societe', 'module' => 'bimpcore', 'startChar' => 31, 'strlen' => 17)));
                $buttons .= BimpRender::renderRowButton('Voir la liste des factures du fichier', 'fas_file-invoice', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des factures du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_Facture', 'module' => 'bimpcommercial', 'startChar' => 48, 'strlen' => 35)));
            }
            if($typeFile == 'achats') {
                $buttons .= BimpRender::renderRowButton('Voir la liste des tiers du fichier', 'fas_user', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des tiers du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_Societe', 'module' => 'bimpcore', 'startChar' => 31, 'strlen' => 17)));
                $buttons .= BimpRender::renderRowButton('Voir la liste des factures du fichier', 'fas_file-invoice', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des factures du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_FactureFourn', 'module' => 'bimpcommercial', 'startChar' => 48, 'strlen' => 35)));
            }
            if($typeFile == 'paiements' || $typeFile == 'deplacementpaiements') {
                $buttons .= BimpRender::renderRowButton('Voir la liste des tiers du fichier', 'fas_user', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des tiers du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_Societe', 'module' => 'bimpcore', 'startChar' => 31, 'strlen' => 17)));
                $buttons .= BimpRender::renderRowButton('Voir la liste des factures du fichier', 'fas_file-invoice', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des factures du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_Facture', 'module' => 'bimpcommercial', 'startChar' => 221, 'strlen' => 35)));
                $buttons .= BimpRender::renderRowButton('Voir la liste des paiements du fichier', 'fas_euro-sign', $tra->getJsLoadModalCustomContent('getObjectFromFile', 'Liste des paiements du fichier ' . basename($file), Array('file' => $file, 'object' => 'Bimp_Paiement', 'module' => 'bimpcommercial', 'startChar' => 48, 'strlen' => 35)));
            }
            //$buttons .= BimpRender::renderRowButton('Vérifier la structure du fichier TRA', 'fas_question', $tra->getJsActionOnclick('verify', Array('file' => $file), Array()));
            $rows[] = [
                'name' => basename($file), 
                'date' => $dateFichier->format('d/m/Y'), 
                'size' => filesize($file), 
                'buttons' => $buttons,
                'struct'=>$this->displayStructureState($file),
            ];
            
            
        }
        
        $panelTitle = ($type != '') ?  'Liste des fichiers ' . $type . ' exportés dans cégid ('.count($rows).' fichiers)' : 'Liste des fichiers en attente d\'envois vers cégid';
        
        $forOpenPanel0 = Array(2, 7, 6);
        
        $panelContent = '';
        if(count($rows) > 0) {
            $panelContent = BimpRender::renderBimpListTable(array_reverse($rows), $headerList, Array('pagination' => 1, 'n' => 10));
        } else {
            $panelContent = BimpRender::renderAlerts('Pas de fichiers', 'warning', false);
        }
        
        $html .= BimpRender::renderPanel($panelTitle, $panelContent,'', Array('open' => $open));
                
        return $html;
        
    }
    
    protected function ajaxProcessSaveFile() {
        
        global $user;
        $errors = Array();
        
        $originalTRA = BimpTools::getPostFieldValue('originalTRA');
        $newTRA = BimpTools::getPostFieldValue('newTRA');
        $fichier = BimpTools::getPostFieldValue('fichier');
        
        $editingFileName = str_replace('.tra', '.editing', $fichier);
        
        if(copy($fichier, $editingFileName)) {
            if($file = fopen($editingFileName, 'w+')) {
                if(fwrite($file, $newTRA)) {
                    if(unlink($fichier)) {
                        rename($editingFileName, str_replace('.editing', '.tra', $editingFileName));
                    } else {
                        unlink($editingFileName);
                        $errors[] = 'Erreur lors de la suppression du fichier d\'origine';
                    }
                } else {
                    unlink($editingFileName);
                    $errors[] = 'Impossible d\'écrire la correction dans le fichier d\'édition ' . print_r(error_get_last(), 1);
                }
            } else {
                unlink($editingFileName);
                $errors[] = 'Erreur lors de l\'ouverture du fichier ' . basename($editingFileName);
            }
        } else {
            $errors[] = 'Impossible de créer une sauvegarde temporaire du fichier';
        }

        die(json_encode(array(
            'success_callback' => 'bimp_reloadPage()',
            'request_id'       => BimpTools::getValue('request_id', 0),
            'errors'           => $errors
        )));
    }


    protected function ajaxProcessSearch(){
        
        if(BimpTools::getPostFieldValue('facture') && BimpTools::getPostFieldValue('searchBy') == 1) {
            die('La recherche par facture n\'est pas encore direponible');
        }
        
        $tra = BimpCache::getBimpObjectInstance('bimptocegid', 'TRA');

        $js .= 'loadModalObjectCustomContent($(this), ' . $tra->getJsObjectData() . ', ';
        $js .= '\'searchResultat\', ';
        $js .= '{'
                . 'auxiliaire: "'.BimpTools::getPostFieldValue('aux').'", '
                . 'searchBy: "'.BimpTools::getPostFieldValue('searchBy').'",'
                . 'facture: "'.BimpTools::getPostFieldValue('facture').'"'
                . '}, ';
        $js .= '\'' . 'Résultat pour '.BimpTools::getPostFieldValue('aux').'' . '\', ';
        $js .= 'null' . ', ';
        $js .= '\'' . 'large' . '\'';
        $js .= ');';
         
         die(json_encode(array(
            'success_callback' => $js,
            'request_id'       => BimpTools::getValue('request_id', 0),
            'errors'           => $errors
        )));
    }
    
    protected function ajaxProcessTransfertFile(){
        
        $cron = new Cron();
        $cron->manualSendTRA();
        
        $js  = 'location.reload()';
        
         die(json_encode(array(
            'success_callback' => $js,
            'request_id'       => BimpTools::getValue('request_id', 0),
            'errors'           => $errors
        )));
    }
    
}
