<?php

require_once DOL_DOCUMENT_ROOT . 'bimptocegid/class/export.class.php';

class cegidController extends BimpController {
    
    protected $version_tra;
    protected $entitie;
    protected $local_path    = PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/' . 'imported_auto' . '/';

    public function renderHeader() {
        
        $this->version_tra = BimpCore::getConf('BIMPTOCEGID_version_tra');
        $this->entitie = BimpCore::getConf('BIMPTOCEGID_file_entity');
        
        return 
        '<div id="bimptocegid__header" class="object_header container-fluid" style="height: auto;">'
            . '<div class="row">'
                . '<div class="col-lg-6 col-sm-8 col-xs-12">'
                    . '<div style="display: inline-block">'
                        . '<div class="object_header_title">'
                            . '<h1>'
                                . '<i class="fas fa5-file-invoice-dollar iconLeft"></i>BimpToCegid'
                            . '</h1>'
                        . '</div>'
                    . '</div>'
                . '</div>'
            . '</div>'
            . '<div class="row header_bottom"></div>'
        . '</div>';
    }
    
    public function renderTiersTab() {
        $html = '';
        
        $instance = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe');
        
        $html .= $this->getExportedFilesArray('tiers');
        
        $list = new BC_ListTable($instance, 'default', 1, null, 'Liste des sociétés exportées');
        $list->addFieldFilterValue('exported', 1);
        $html .= $list->renderHtml();
        
        return $html;
    }
    
    private function getExportedFilesArray($type) {
        
        $html = '';
        
        $tra = BimpCache::getBimpObjectInstance('bimptocegid', 'TRA');
        
        switch($type) {
            case 'tiers': $number = '0'; $typeFile = 'tiers'; break;
        }
        
        $pattern = $number . '_' . $this->entitie . '_(' . strtoupper($typeFile) . ')_' . '*' . '_' . $this->version_tra . '.tra';
        
        $headerList = [
            'name'      => 'Nom du fichier',
            'date'      => 'Date du fichier',
            'size'      => 'Taille du fichier',
            'buttons'   => ['label' => '', 'col_style' => 'text-align: right']
        ];
        
        $rows = [];
        
        $dateFichier = new DateTime();
        
        foreach(glob($this->local_path . $pattern) as $file) {
            $dateFichier->setTimestamp(fileatime($file));
            $buttons = '';
            $buttons .= BimpRender::renderRowButton('Voir le contenue du fichier', 'fas_eye', $tra->getJsLoadModalCustomContent('displayTraFile', basename($file), Array('file' => $file)));
            $buttons .= BimpRender::renderRowButton('Voir la liste des tiers du fichier', 'fas_user', $tra->getJsLoadModalCustomContent('getTiersFromfile', 'Liste des tiers du fichier ' . basename($file), Array('file' => $file, 'type' => 'tiers')));
            $rows[] = ['name' => basename($file), 'date' => $dateFichier->format('d/m/Y'), 'size' => filesize($file), 'buttons' => $buttons];
        }
        
        $html .= BimpRender::renderPanel('Liste des fichiers ' . $type . ' exportés dans cégid', BimpRender::renderBimpListTable($rows, $headerList));
                
        return $html;
        
    }
    
    
}
