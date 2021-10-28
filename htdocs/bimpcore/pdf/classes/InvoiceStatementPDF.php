<?php

require_once __DIR__ . '/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

class InvoiceStatementPDF extends BimpDocumentPDF
{

    public static $type = 'societe';
    public $commandes = array();
    public $shipments = array();
    public $deliveries = array();
    public $nb_deliveries = 0;
    public $date_debut = null;
    public $date_fin = null;
    private $bimpDb;
    public $factures = array();
    public static $use_cgv = false;
    public $total_ttc = 0;
    public $total_rap = 0;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->bimpDb = new BimpDb($db);

        $this->langs->load("bills");
        $this->langs->load("products");
        $this->typeObject = "societe";
    }

    protected function initData()
    {
        parent::initData();

        $this->date_debut = new DateTime($this->object->borne_debut);
        $this->date_fin = new DateTime($this->object->borne_fin);
        $this->thirdparty = $this->object;

        $filters = array(
            'fk_soc'    => $this->object->id,
            'fk_statut' => array(
                'in' => array(1, 2)
            ),
            'datef'     => array(
                'min' => $this->object->borne_debut,
                'max' => $this->object->borne_fin
        ));

        $this->factures = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', $filters, 'datec', 'desc');
    }

    protected function initHeader()
    {
        $docRef = "Du " . $this->date_debut->format('d/m/Y') . ' au ' . $this->date_fin->format('d/m/Y');

        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;

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

        $this->pdf->topMargin = 44;

        $this->header_vars = array(
            'logo_img'      => $logo_file,
            'logo_width'    => $logo_width,
            'logo_height'   => $logo_height,
            'header_infos'  => $this->getSenderInfosHtml(),
            'header_right'  => $header_right,
            'primary_color' => $this->primary,
            'doc_name'      => 'Relevé facturation',
            'doc_ref'       => $docRef,
            'ref_extra'     => ''
        );
    }

    public function getFileName()
    {
        return 'Releve_facturation'; //_' . $this->date_debut->format('d_m_Y') .'_a_'. $this->date_fin->format('d_m_Y');
    }

    public function renderTop()
    {
        
    }
    
    public function renderLines()
    {

        if (empty($this->factures)) {
            $this->writeContent('<p style="font-weight: bold; font-size: 12px">Aucune facture</p>');
        } else {
            $table = new BimpPDF_Table($this->pdf);

            $table->addCol('ref', 'Référence', 23, 'text-align: left;', '', 'text-align: left;');
            $table->addCol('libelle', 'Libellé', 0, 'text-align: left;', '', 'text-align: left;');
            $table->addCol('livraison', 'Contact livraison', 28, 'text-align: left;', '', 'text-align: left;');
            $table->addCol('date', 'Date', 20, 'text-align: center;', '', 'text-align: center;');
            $table->addCol('echeance', 'Echeance', 20, 'text-align: center;', '', 'text-align: center;');
            $table->addCol('total_ttc', 'Montant TTC', 23, 'text-align: center;', '', 'text-align: center;');
            $table->addCol('paiement', 'Paiement', 18, 'text-align: center;', '', 'text-align: center;');
            $table->addCol('remain', 'Reste à payer', 23, 'text-align: center;', '', 'text-align: center;');

            foreach ($this->factures as $facture) {
                $rap = $facture->getRemainToPay();
                $this->total_ttc += round((float) $facture->getData('total_ttc'), 2);
                $this->total_rap += $rap;

                $row = array(
                    'ref'       => $facture->getData('facnumber'),
                    'libelle'   => $facture->displayData('libelle', 'default', false, true),
                    'date'      => $facture->displayData('datef', 'default', false, true),
                    'echeance'  => $facture->displayData('date_lim_reglement', 'default', false, true),
                    'total_ttc' => BimpTools::displayMoneyValue($facture->getData("total_ttc"), 'EUR', 0, 0, 1, 2, 0, ',', 1),
                    'remain'      => BimpTools::displayMoneyValue($rap, 'EUR', 0, 0, 1, 2, 0, ',', 1),
                );

                if (isset(Bimp_Facture::$paiement_status[(int) $facture->getData('paiement_status')]['short'])) {
                    $row['paiement'] = Bimp_Facture::$paiement_status[(int) $facture->getData('paiement_status')]['short'];
                } else {
                    $row['paiement'] = Bimp_Facture::$paiement_status[(int) $facture->getData('paiement_status')]['label'];
                }

                $id_contacts = $this->bimpDb->getValues('bl_commande_shipment', 'id_contact', 'id_facture = ' . $facture->id);

                if (is_array($id_contacts) && count($id_contacts) > 0) {
                    $fl = true;
                    foreach ($id_contacts as $id_contact) {
                        if ($id_contact > 0) {
                            if (!$fl) {
                                $row['livraison'] .= '<br/>';
                            } else {
                                $fl = false;
                            }

                            $socp = $this->bimpDb->getRow('socpeople', 'rowid = ' . $id_contact);
                            if (!is_null($socp)) {
                                $row['livraison'] .= ' - ' . $socp->lastname . ' ' . $socp->firstname;
                            } else {
                                $row['livraison'] .= ' - Contact #' . $id_contact . ' supprimé';
                            }
                        }
                    }
                } else {
                    $row['livraison'] = $this->object->nom;
                }

                $table->rows[] = $row;
            }

            $table->write();
        }
    }

    public function getBottomRightHtml()
    {
        $html .= $this->getTotauxRowsHtml();

        return $html;
    }

    public function calcTotaux()
    {
        
    }

    public function getTotauxRowsHtml()
    {
        $html = "";

        $html .= '<table style="width: 100%" cellpadding="5">';

        $html .= '<tr>';
        $html .= '<td style="background-color: #F0F0F0;">Total factures TTC</td>';
        $html .= '<td style="text-align: right;background-color: #F0F0F0;">';
        $html .= BimpTools::displayMoneyValue($this->total_ttc, '', 0, 0, 1, 2);
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td style="background-color: #DCDCDC;">Total reste à payer</td>';
        $html .= '<td style="text-align: right;background-color: #DCDCDC;">';
        $html .= BimpTools::displayMoneyValue($this->total_rap, '', 0, 0, 1, 2);
        $html .= '</td>';
        $html .= '</tr>';

//        $html .= '<tr>';
//        $html .= '<td style="">Solde</td>';
//        $html .= '<td style="text-align: right;">';
//        $html .= BimpTools::displayMoneyValue($this->total_ttc - $this->total_paid, '', 0, 0, 1, 2);
//        $html .= '</td>';
//        $html .= '</tr>';

        $html .= '</table>';

        return $html;
    }

    public function renderAfterLines()
    {
        
    }
}
