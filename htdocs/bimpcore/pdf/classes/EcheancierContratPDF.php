<?php 
    require_once __DIR__ . '/BimpCommDocumentPDF.php';

    class InterStatementPDF extends BimpCommDocumentPDF
    {

        public static $type = 'contract';
        public $contrat;
        public $echeancier;
        public $client;
        public $signature_bloc = true;

        public function __construct($db)
        {
            static::$use_cgv = false;
            parent::__construct($db);
            $this->bimpDb = new BimpDb($db);

            $this->langs->load("bills");
            $this->langs->load("products");
            $this->typeObject = "contract";
        }

        protected function initData() {
            $this->thirdparty = $this->object;
            
            $echeancier         = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
            $this->contrat      = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->object->id);
            $this->client             = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $this->contrat->getData('fk_soc'));
            
            if($echeancier->find(Array('id_contrat' => $this->contrat->id), 1)) {
                $this->echeancier = $echeancier;
            }
            
            parent::initData();
            
        }

        protected function initHeader()
        {
            $docRef = $this->contrat->getRef() . '<br />&Eacute;tat au ' . date('d/m/Y');

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

                $tabTaille = $this->calculeWidthHeightLogo($sizes['width'], $sizes['height'], $this->maxLogoWidth, $this->maxLogoHeight);

                $logo_width = $tabTaille[0];
                $logo_height = $tabTaille[1];
            }

            $header_right = '';

            if (isset($this->object->logo) && (string) $this->object->logo) {
                $soc_logo_file = DOL_DATA_ROOT . '/societe/' . $this->object->id . '/logos/' . $this->object->logo;
                if (file_exists($soc_logo_file)) {
                    $sizes = dol_getImageSize($soc_logo_file, false);
                    if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {
                        $tabTaille = $this->calculeWidthHeightLogo($sizes['width'], $sizes['height'], 80, 80);

                        $header_right = '<img src="' . $soc_logo_file . '" width="' . $tabTaille[0] . 'px" height="' . $tabTaille[1] . 'px"/>';
                    }
                }
            }

            //$docRef .= "<br/>Commercial : ".$this->client->displayCommercials(true, false);

            $this->pdf->topMargin = 44;

            $this->header_vars = array(
                'logo_img'      => $logo_file,
                'logo_width'    => $logo_width,
                'logo_height'   => $logo_height,
                'header_infos'  => $this->getSenderInfosHtml(),
                'header_right'  => $header_right,
                'primary_color' => $this->primary,
                'doc_name'      => '&Eacute;chéancier',
                'doc_ref'       => $docRef,
                'ref_extra'     => ''
            );

        }

        public function getFileName()
        {
            return 'Echeancier.pdf';
        }

        public function renderTop()
        {

        }
        
        public function renderDocInfos() {
            $html = '';

            $html .= '<div class="section addresses_section">';
            $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
            $html .= '<tr>';
            $html .= '<td style="width: 55%"></td>';
            $html .= '<td style="width: 5%"></td>';
            $html .= '<td class="section_title" style="width: 40%; border-top: solid 1px #' . $this->primary . '; border-bottom: solid 1px #' . $this->primary . '">';
            $html .= '<span style="color: #' . $this->primary . '">DESTINATAIRE</span></td>';
            $html .= '</tr>';
            $html .= '</table>';

            $html .= '<table style="width: 100%" cellspacing="0" cellpadding="10px">';
            $html .= '<tr>';
            $html .= '<td class="sender_address" style="width: 55%">';
            $html .= $this->getDocInfosHtml();
            $html .= '</td>';
            $html .= '<td style="width: 5%"></td>';
            $html .= '<td style="width: 40%">';
            
            $html .= $this->client->getName() . '<br />';
            $html .= $this->client->getData('address') . '<br />';
            $html .= $this->client->getData('zip') . ', ' . $this->client->getData('town');
            
            //$html .= $this->getTargetInfosHtml();

            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';

            $this->writeContent($html);
            
        }

        public function renderLines()
        {
            
            
            $allPeriodes = $this->echeancier->getAllPeriodes();
            
            if(count($allPeriodes['periodes']) > 0) {
                $this->writeContent('<p style="font-weight: bold; font-size: 12px">Echéancier pour ' . ($allPeriodes['infos']['nombre_periodes'] + $allPeriodes['infos']['periode_incomplette_mois'] ) . ' ' . (($allPeriodes['infos']['nombre_periodes'] + $allPeriodes['infos']['periode_incomplette_mois'] > 1) ? 'périodes' : 'periode') . '</p>');
                $table = new BimpPDF_Table($this->pdf, true, $this->primary);
            
                $table->addCol('periode', 'Période de référence', 20, 'text-align: left;', '', 'text-align: left;');
                $table->addCol('datePrelevement', 'Date de prélèvement', 20, 'text-align: center;', '', 'text-align: center;');
                $table->addCol('ht', 'Montant HT', 20, 'text-align: center;', '', 'text-align: center;');
                $table->addCol('tva', 'Montant TVA', 20, 'text-align: center;', '', 'text-align: center;');
                $table->addCol('ttc', 'Montant TTC', 20, 'text-align: center;', '', 'text-align: center;');
                $table->addCol('facture', 'Facture', 20, 'text-align: center;', '', 'text-align: center;');
                
                                
                foreach($allPeriodes['periodes'] as $periode) {
                    $total_ht += round($periode['PRICE'], 2);
                    $total_tva += round($periode['TVA'], 2);
                    $total_ttc += (round($periode['PRICE'], 2) + round($periode['TVA'], 2));
                    $table->rows[] = array(
                        'periode'           => 'Du ' . $periode['START'] . ' au ' . $periode['STOP'] . ' ('.$periode['DUREE_MOIS'].' mois) ',
                        'facture'           => ($periode['FACTURE'] != '') ? $periode['FACTURE'] : 'Periode non facturée',
                        'datePrelevement'   => $periode['DATE_FACTURATION'],
                        'ht'                => round($periode['PRICE'], 2) . '€',
                        'tva'               => round($periode['TVA'], 2) . '€',
                        'ttc'               => (round($periode['PRICE'], 2) + round($periode['TVA'], 2)). '€',
                    );
                    
                }
                
                if($this->object->afficher_total) {
                    $table->rows[] = array(
                        'periode'           => '',
                        'facture'           => '',
                        'datePrelevement'   => '<b style="text-align:right" >TOTAL</b>',
                        'ht'                => '<b>'.$total_ht.'€</b>',
                        'tva'               => '<b>'.$total_tva.'€</b>',
                        'ttc'               => '<b>'.$total_ttc.'€</b>',
                    );
                }

                $table->write();
//                $html = $this->getTotauxRowsHtml($total_ht);
//                $this->writeContent(print_r($allPeriodes['infos']['factures'], 1));
            } else {
                $this->writeContent('<p style="font-weight: bold; font-size: 12px">Aucune période pour ce contrat</p>');
            }
            
        }

        public function getBottomRightHtml()
        {

            return $html;
        }

        public function calcTotaux()
        {

        }

        public function getTotauxRowsHtml($montant)
        {
            $html = "";
            $reste = 0;



            $html .= '<table style="width: 100%" cellpadding="5">';
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">Total HT</td>';
            $html .= '<td style="text-align: center;background-color: #F0F0F0;">';
            $html .= $montant;
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';

            return $html;
        }

        public function renderAfterLines()
        {

        }
    }
