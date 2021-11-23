<?php

class newExportController extends BimpController {

    protected $entitie = "";
    protected $startExport = null;
    protected $stopExport = null;
    protected $path_imported = "";
    protected $export = null;

    public function displayHeaderInterface() {
        
        if(!$this->entitie) $this->entitie = BimpCore::getConf('BIMPTOCEGID_file_entity');
        if(!$this->startExport) {
            $this->startExport = new DateTime(BimpCore::getConf('BIMPTOCEGID_last_export_date'));
            $this->startExport->add(new DateInterval("P1D"));
        }
        if(!$this->stopExport) $this->stopExport = new DateTime();
        if(!$this->path_imported) $this->path_imported = PATH_TMP  ."/" . 'exportCegid' . '/' . 'BY_DATE' . '/' . "imported_auto" . '/';
        if(!$this->export) $this->export = BimpCache::getBimpObjectInstance('bimptocegid', 'BTC_export'); 
        
        $header = '<div id="'.$this->entitie.'_compta_header" class="object_header container-fluid">
                    <div class="row">
                        <div class="col-lg-6 col-sm-8 col-xs-12">
                            <div style="display: inline-block">
                                <div class="object_header_title">
                                    <h1>
                                        <i class="fas fa5-file-export iconLeft"></i>Exports comptable de '.$this->entitie.'
                                    </h1>
                                </div>
                                <div class="header_extra">
                                    <div style="margin: 10px 0;">
					<div style="margin-bottom: 8px">
                                            <i class="fas fa5-calendar-alt iconLeft"></i>Prochain export: du <strong>'.$this->startExport->format('d/m/Y').'</strong> au <strong>'.$this->stopExport->format('d/m/Y').'</strong><br />
					</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-4 col-xs-12" style="text-align: right">
                            <div class="header_status">
                                <div class="header_buttons"><button class="btn btn-default" type="button" onclick="'.$this->export->getJsActionOnclick("search_piece", array(), array("form_name" => "search_piece")).'"><i class="fas fa5-search iconLeft"></i>Chercher une pièce comptable dans les fichiers TRA</button></div>
                            </div>
                        </div>
                    </div>
                  </div>';
        
        return $header;
    }
    
    public function displayLogsList()  {
        
        $object = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_Report');
        
        
        $filters = [];
        $html .= $object->renderList('default', true, null, null, $filters);
        
        return $html;
    }
    
    public function displayFileExported($type) {
        switch($type) {
            case 'tier':
                $title = 'Exported tiers TRA files';
                $no_linked = "Pas de fichiers TRA pour les tiers afficher";
                $patern = '0_' . $this->entitie . '_(TIERS)_';
                break;
            case 'pay':
                $title = 'Exported Paiements TRA files';
                $no_linked = "Pas de fichiers TRA pour les paiementsà afficher";
                $patern = '2_' . $this->entitie . '_(PAIEMENTS)_';
                break;
            case 'rib':
                $title = 'Exported RIB TRA files';
                $no_linked = "Pas de fichiers TRA pour les RIBs à afficher";
                $patern = '4_' . $this->entitie . '_(RIBS)_';
                break;
            case 'mandat':
                $title = 'Exported MANDATS TRA files';
                $no_linked = "Pas de fichiers TRA pour les MMANDATS à afficher";
                $patern = '5_' . $this->entitie . '_(MANDATS)_';
                break;
            case 'payni':
                $title = 'Exported Paiements non identifiés TRA files';
                $no_linked = "Pas de fichiers TRA pour les paiements non identifiés à afficher";
                $patern = '6_' . $this->entitie . '_(PAYNI)_';
                break;
        }
       
        $headers = array(
            'path'   => 'Chemin du fichier',
            'size' => array('label' => 'Taille', 'align' => 'center', 'searchable' => 0),
            'date'  => array('label' => 'Date', 'align' => 'center', 'searchable' => 0),
            'action' => array('label' => '', 'align' => 'right', 'searchable' => 0),
        );
        
        $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                    'searchable' => true,
                    'sortable'   => true,
                    'checkboxes' => false
        ));
        
//        $html .= '<table class="bimp_list_table">';
//            $html .= '<thead>';
//            $html .= '<tr>';
//            $html .= '<th>Fichier</th>';
//            $html .= '<th>Type</th>';
//            $html .= '<th>Taille</th>';
//            $html .= '<th>Date</th>';
//            $html .= '<th></th>';
//            $html .= '</tr>';
//            $html .= '</thead>';
//
//            $html .= '<tbody>';
//            
//            $glob = glob($this->path_imported . $patern . '*' . date('Y') . '*');
//            if (count($glob)) {
//                foreach ($glob as $filePath) {
//                    $time = new DateTime();
//                    $time->setTimestamp(fileatime($filePath));
//                    $htmlP .= '<tr>';
//                    $htmlP .= '<td><strong>' . $filePath . '</strong></td>';
//                    $htmlP .= '<td>'. filetype($filePath).'</td>';
//                    $htmlP .= '<td>'. filesize($filePath).'</td>';
//                    $htmlP .= '<td>'. $time->format('d/m/Y') .'</td>';
//                    $htmlP .= '<td class="buttons"> ' . '<span class="rowButton bs-popover" '.BimpRender::renderPopoverData('Voir le contenu du fichier').' onclick="'. $this->export->getJsLoadModalCustomContent('display_content_file', "Contenu du fichier", ['path' => $filePath]) .'" ><i class="far fa5-eye" ></i></span>' . ' </td>';
//                    $htmlP .= '</tr>';
//                }
//            }
//            if ($htmlP == '') {
//                $htmlP .= '<tr>';
//                $htmlP .= '<td colspan="5">' . BimpRender::renderAlerts($no_linked, 'info') . '</td>';
//                $htmlP .= '</tr>';
//            }
//
//            $html .= $htmlP;
//            $html .= '</tbody>';
//            $html .= '</table>';
//
//            $html = BimpRender::renderPanel($title, $html, '', array(
//                'foldable' => true,
//                'type'     => 'secondary-forced',
//                'icon'     => 'fas_link',
//            ));
            
            return BimpRender::renderPanel('Liste des droits', $html, '', array(
                    'foldable' => true,
                    'type'     => 'secondary'
        ));
    }

}