<?php

require_once __DIR__ . '/BimpDocumentPDF.php';

class InterStatementPDF extends BimpDocumentPDF
{

    public static $type = 'fichinter';
    public $commandes = array();
    public $contrat = array();
    public $interventions = array();
    public $date_document = null;
    
    public $date_start = null;
    public $date_stop = null;
    public $tech = null;
    public $client = null;
    public $signature_bloc = false;
    public $target_label = 'test';
    public $filters = Array();
    public $string_filters = '';
    
    private $bimpDb;
    public $total_time = 0;
    
    public function __construct($db)
    {
        static::$use_cgv = false;
        parent::__construct($db);
        $this->bimpDb = new BimpDb($db);

        $this->langs->load("bills");
        $this->langs->load("products");
        $this->typeObject = "societe";
    }

    protected function initData() {
        $this->thirdparty = $this->object;
        parent::initData();
        $this->date_document = new DateTime();

        if($this->object->date_start_relever || $this->object->date_stop_relever) {
            $this->date_start = new DateTime($this->object->date_start_relever);
            $this->date_stop = new DateTime($this->object->date_stop_relever);
            $this->string_filters .= 'Du ' . $this->date_start->format('d/m/Y') . ' au ' . $this->date_stop->format('d/m/Y') . '<br /><br />';
            $this->filters['datei'] = array('min' => $this->object->date_start_relever, 'max' => $this->object->date_stop_relever);
        }
        
        $this->string_filters .= 'Code client : '.$this->object->code_client.'<br/>Code compta : '.$this->object->code_compta;

        if($this->object->id_tech > 0) {
            $this->tech = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->object->id_tech);
            $this->string_filters .= 'Technicien: ' . $this->tech->getName() . '<br />';
            $this->filters['fk_user_tech'] = $this->tech->id;
        }
        
        $this->client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->object->id);
//        $this->string_filters .= '<br/> Client: ' . $this->client->getName() . '<br />';
        $this->filters['fk_soc'] = $this->client->id;
        
        
        if($this->object->id_contrat > 0) {
            $this->want_contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->object->id_contrat);
            $this->client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->want_contrat->getData('fk_soc'));
            $this->string_filters .= '<br />Contrat: ' . $this->want_contrat->getRef() . '<br /><br />';
            $this->filters['fk_contrat'] = $this->want_contrat->id;
        }
        
        
    }

    protected function initHeader()
    {
        $docRef = $this->string_filters . '<br />';

        global $conf;
        $logo_file = $conf->mycompany->dir_output . '/logos/' . str_replace('.png', '_PRO.png', $this->fromCompany->logo);

        $logo_width = 0;
        if (!file_exists($logo_file)) {
            $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;
        }   

        $logo_width = 0;
        if (!file_exists($logo_file)) {
            $logo_file = '';
        } else {
            $sizes = dol_getImageSize($logo_file, false);

            $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'], $sizes['height'], $this->maxLogoWidth, $this->maxLogoHeight);

            $logo_width = $tabTaille[0];
            $logo_height = $tabTaille[1];
        }

        $header_right = '';

        if (isset($this->object->logo) && (string) $this->object->logo) {
            $soc_logo_file = DOL_DATA_ROOT . '/societe/' . $this->object->id . '/logos/' . $this->object->logo;
            if (file_exists($soc_logo_file)) {
                $sizes = dol_getImageSize($soc_logo_file, false);
                if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {
                    $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'], $sizes['height'], 80, 80);

                    $header_right = '<img src="' . $soc_logo_file . '" width="' . $tabTaille[0] . 'px" height="' . $tabTaille[1] . 'px"/>';
                }
            }
        }
        
        $docRef .= "<br/>Commercial : ".$this->client->displayCommercials(true, false);

        $this->pdf->topMargin = 44;

        $this->header_vars = array(
            'logo_img'      => $logo_file,
            'logo_width'    => $logo_width,
            'logo_height'   => $logo_height,
            'header_infos'  => $this->getSenderInfosHtml(),
            'header_right'  => $header_right,
            'primary_color' => $this->primary,
            'doc_name'      => 'Relevé d\'interventions',
            'doc_ref'       => $docRef,
            'ref_extra'     => ''
        );
        
    }
    
    public function getFileName()
    {
        return 'Releve_interventions.pdf';
    }

    public function renderTop()
    {
        
    }
    
    public function renderLines()
    {
        $intervention = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter');
        $inters_valid = $intervention->getList(BimpTools::merge_array($this->filters, Array('fk_statut' => array('operator' => '>', 'value' => 0))));
                
        if (!count($inters_valid)) {
            $this->writeContent('<p style="font-weight: bold; font-size: 12px">Aucune intervention effectuées</p>');
        } else {
            
            $this->writeContent('<h3>Interventions effectuées</h3>');
            
            $table = new BimpPDF_Table($this->pdf);
            
            $table->addCol('ref', 'N° fiche intervention', 20, 'text-align: left;', '', 'text-align: left;');
            $table->addCol('contrat', 'Contrat', 20, 'text-align: left;', '', 'text-align: left;');
            $table->addCol('tech', 'Intervenant', 28, 'text-align: left;', '', 'text-align: left;');
            $table->addCol('date', 'Date d\'intervention', 20, 'text-align: center;', '', 'text-align: center;');
            $table->addCol('urgent', 'Intervention urgente', 23, 'text-align: center;', '', 'text-align: center;');
            $table->addCol('temps_passer', 'Temps passé', 25, 'text-align: center;', '', 'text-align: center;');
            
            foreach($inters_valid as $data) {
                $instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter', $data['rowid']);
                
                $this->total_time += $instance->getData('duree');
                
                if($instance->getData('fk_contrat')){
                    $this->contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $instance->getData('fk_contrat'));
                }
                
                $table->rows[] = array(
                    'ref'           => $instance->getRef(),
                    'contrat'       => ($this->contrat) ? $this->contrat->getRef() . ' (' . $this->contrat->displayData('statut') . ')' : 'Non liée',
                    'tech'          => $instance->displayData('fk_user_tech', 'default', false, true),
                    'date'          => $instance->displayData('datei', 'default', false, true),
                    'urgent'        => $instance->displayData('urgent', 'default', false, true),
                    'temps_passer'  => BimpTools::displayTimefromSeconds($instance->getData('duree'), '', 0, 0, 1, 2),
                );
                $this->contrat = null;
            }
            $table->write();
        }
        
        
        if(isset($this->want_contrat) && is_object($this->want_contrat)){
            $vendue = $this->want_contrat->getDurreeVendu();
        }
        $html = $this->getTotauxRowsHtml($this->total_time, $vendue);
        $this->writeContent($html);
        
        $inters_valid_nok = $intervention->getList(BimpTools::merge_array($this->filters, Array('fk_statut' => array('operator' => '=', 'value' => 0))));
            
            if(!count($inters_valid_nok)) {
                $this->writeContent('<br /><p style="font-weight: bold; font-size: 12px">Aucune intervention à venir / en cours</p>');
            } else {
                $this->writeContent('<br /><h3>Interventions à venir / en cours</h3>');
                $table2 = new BimpPDF_Table($this->pdf);
            
                $table2->addCol('ref', 'N° fiche intervention', 20, 'text-align: left;', '', 'text-align: left;');
                $table2->addCol('contrat', 'Contrat', 20, 'text-align: left;', '', 'text-align: left;');
                $table2->addCol('tech', 'Intervenant', 28, 'text-align: left;', '', 'text-align: left;');
                $table2->addCol('date', 'Date d\'intervention', 20, 'text-align: center;', '', 'text-align: center;');
                $table2->addCol('urgent', 'Intervention urgente', 23, 'text-align: center;', '', 'text-align: center;');
                $table2->addCol('temps_passer', 'Temps passé', 25, 'text-align: center;', '', 'text-align: center;');

                foreach($inters_valid_nok as $data) {
                    $instance = BimpCache::getBimpObjectInstance('bimptechnique', 'BT_ficheInter', $data['rowid']);

//                    $this->total_time += $instance->getData('duree');

                    if($instance->getData('fk_contrat')){
                        $this->contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $instance->getData('fk_contrat'));
                    }

                    $table2->rows[] = array(
                        'ref'           => $instance->getRef(),
                        'contrat'       => ($this->contrat) ? $this->contrat->getRef() . ' (' . $this->contrat->displayData('statut') . ')' : 'Non liée',
                        'tech'          => $instance->displayData('fk_user_tech', 'default', false, true),
                        'date'          => $instance->displayData('datei', 'default', false, true),
                        'urgent'        => $instance->displayData('urgent', 'default', false, true),
                        'temps_passer'  => BimpTools::displayTimefromSeconds($instance->getData('duree'), '', 0, 0, 1, 2),
                    );
                    $this->contrat = null;
                }
                
                $table2->write();
            }
    }

    public function getBottomRightHtml()
    {

        return $html;
    }

    public function calcTotaux()
    {
        
    }

    public function getTotauxRowsHtml($realisee, $vendue = 0)
    {
        $html = "";
        $reste = 0;
        
        

        $html .= '<table style="width: 100%" cellpadding="5">';

        if($vendue > 0){
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">Vendue</td>';
            $html .= '<td style="text-align: center;background-color: #F0F0F0;">';
            $html .= BimpTools::displayTimefromSeconds($vendue, 0);
            $html .= '</td>';
            $html .= '</tr>';
            $reste = $vendue - $realisee;
        }
        $html .= '<tr>';
        $html .= '<td style="background-color: #F0F0F0;">Temps total consommé</td>';
        $html .= '<td style="text-align: center;background-color: #F0F0F0;">';
        $html .= BimpTools::displayTimefromSeconds($realisee, 0);
        $html .= '</td>';
        $html .= '</tr>';
        if($reste != 0){
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">Restant</td>';
            $html .= '<td style="text-align: center;background-color: #F0F0F0;">';
            $html .= BimpTools::displayTimefromSeconds($reste, 0);
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    public function renderAfterLines()
    {
        
    }
}
