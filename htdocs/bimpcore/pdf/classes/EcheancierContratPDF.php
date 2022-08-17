<?php 
    require_once __DIR__ . '/BimpDocumentPDF.php';

    class InterStatementPDF extends BimpDocumentPDF
    {

        public static $type = 'contract';
        public $contrat;
        public $echeancier;

        public function __construct($db)
        {
            static::$use_cgv = false;
            parent::__construct($db);
            $this->bimpDb = new BimpDb($db);

            $this->langs->load("bills");
            $this->langs->load("products");
            $this->typeObject = "contrat";
        }

        protected function initData() {
            $this->thirdparty = $this->object;
            
            $echeancier         = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
            $this->contrat      = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', $this->object->id);
            
            if($echeancier->find(Array('id_contrat' => $this->contrat->id), 1)) {
                $this->echeancier = $echeancier;
            }
            
            parent::initData();
            
        }

        protected function initHeader()
        {
            $docRef = $this->contrat->getRef();

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

        public function renderLines()
        {
            
            
            $allPeriodes = $this->echeancier->getAllPeriodes();
            
            if(count($allPeriodes['periodes']) > 0) {
                $this->writeContent('<p style="font-weight: bold; font-size: 12px">Echéancier pour ' . ($allPeriodes['infos']['nombre_periodes'] + $allPeriodes['infos']['periode_incomplette_mois'] ) . ' ' . (($allPeriodes['infos']['nombre_periodes'] + $allPeriodes['infos']['periode_incomplette_mois'] > 1) ? 'périodes' : 'periode') . '</p>');
                $table = new BimpPDF_Table($this->pdf);
            
                $table->addCol('periode', 'Période de référence', 20, 'text-align: left;', '', 'text-align: left;');
                $table->addCol('datePrelevement', 'Date de prélèvement', 20, 'text-align: center;', '', 'text-align: center;');
                $table->addCol('ht', 'Montant HT', 20, 'text-align: center;', '', 'text-align: center;');
                $table->addCol('tva', 'Montant TVA', 20, 'text-align: center;', '', 'text-align: center;');
                $table->addCol('ttc', 'Montant TTC', 20, 'text-align: center;', '', 'text-align: center;');
                
                foreach($allPeriodes['periodes'] as $periode) {
                    $total_ht += round($periode['PRICE'], 2);
                    $total_tva += round($periode['TVA'], 2);
                    $total_ttc += (round($periode['PRICE'], 2) + round($periode['TVA'], 2));
                    $table->rows[] = array(
                        'periode'           => 'Du ' . $periode['START'] . ' au ' . $periode['STOP'] . ' ('.$periode['DUREE_MOIS'].' mois) ',
                        'datePrelevement'   => $periode['DATE_FACTURATION'],
                        'ht'                => round($periode['PRICE'], 2) . '€',
                        'tva'               => round($periode['TVA'], 2) . '€',
                        'ttc'               => (round($periode['PRICE'], 2) + round($periode['TVA'], 2)). '€'
                    );
                    
                }
                
                $table->rows[] = array(
                    'periode'           => '',
                    'datePrelevement'   => '<b style="text-align:right" >TOTAL</b>',
                    'ht'                => '<b>'.$total_ht.'€</b>',
                    'tva'               => '<b>'.$total_tva.'€</b>',
                    'ttc'               => '<b>'.$total_ttc.'€</b>',
                );
                

                $table->write();
//                $html = $this->getTotauxRowsHtml($total_ht);
//                $this->writeContent($html);
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
