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
    }

    protected function initHeader()
    {
        parent::initHeader();
        $docName = 'Relevé facturation';
        $this->date_debut = new DateTime($this->object->borne_debut);
        $this->date_fin = new DateTime($this->object->borne_fin);
//        $docRef = "Du " . $this->date_debut->format('d/m/Y') . ' au ' . $this->date_fin->format('d/m/Y');
        //$this->getDocInfosHtml();
        $this->header_vars['doc_name'] = $docName;
        $this->header_vars['doc_ref'] = '';
    }

    public function getFileName()
    {
        return 'Relevé_facturation';
    }

    public function renderContent()
    {

        $html = '';
        $html .= '<div class="section addresses_section">';
        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="1px">';

        $html .= '<tr>';
        $html .= '<td style="width: 40%"></td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 55%">' . $this->langs->transnoentities('BillTo') . ' du document : </td>';
        $html .= '</tr>';

        $html .= '</table>';


        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="10px">';
        $html .= '<tr>';
        $html .= '<td class="sender_address" style="width: 40%">';
        $html .= 'Relevé du '. $this->date_debut->format('d / m / Y').' au ' . $this->date_fin->format('d / m / Y') .'<br/>';
        $html .= 'Emis en date du ' . date('d / m / Y');
        $html .= '</td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 55%" class="border">';

        $html .= '<b>' . $this->object->nom . '</b><br />' . $this->object->address . '<br />' . $this->object->zip . ', ' . $this->object->town;
        $html .= '<br />Référence client : ' . $this->object->code_client;
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div class="section">';
        $html .= '<table style="width: 100%; font-size:8px" cellspacing="0" cellpadding="3px">';

        $html .= '<tr style="color:' . $this->primary . ' " >';
        $html .= '<td style="width:15%">Référence</td>';
        $html .= '<td style="width:22%">Libellé</td>';
        $html .= '<th style="width:10%">Date</th>';
        $html .= '<th style="width:10%">Echeance</th>';
        $html .= '<th style="width:15%">Paiement</th>';
        $html .= '<th style="width:15%">Contact de livraison</th>';
        $html .= '<th style="width:13%">Montant TTC</th>';
        $html .= '</tr>';

        $list = $this->bimpDb->getRows('facture', 'fk_soc = ' . $this->object->id . ' AND datef BETWEEN "' . $this->date_debut->format('Y-m-d') . '" and "' . $this->date_fin->format('Y-m-d') . '" AND fk_statut IN (1,2,3)');
        $total_facture = 0;

        foreach ($list as $facture) {
            $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', $facture->rowid);
            $total_facture += round($instance->getData("total_ttc"), 2);

            $html .= '<tr>';
            $date_in_facture = new DateTime($instance->getData('datef'));
            $html .= '<td>' . $instance->getData('facnumber') . '</td>';
            $html .= '<td>' . $instance->getData('libelle') . '</td>';
            $html .= '<td>' . $date_in_facture->format('d/m/Y') . '</td>';
            $date_in_facture = new DateTime($instance->getData('date_lim_reglement'));
            $html .= '<td>' . $date_in_facture->format('d/m/Y') . '</td>';

            $html .= '<td>' . Bimp_Facture::$paiement_status[(int) $instance->getData('paiement_status')]['label'] . '</td>';

            $id_contact = $this->bimpDb->getValue('bl_commande_shipment', 'id_contact', 'id_facture = ' . $instance->id);

            if ($id_contact) {
                $socp = $this->bimpDb->getRow('socpeople', 'rowid = ' . $id_contact);
                $html .= '<td>' . $socp->lastname . ' ' . $socp->firstname . '</td>';
            } else {
                $html .= '<td>' . $this->object->nom . '</td>';
            }
            $html .= '<td style="text-align: right">' . BimpTools::displayMoneyValue($instance->getData("total_ttc"), 'EUR', 0, 0, 1, 2, 0, ',', 1) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr><td></td><td></td><td></td><td></td><td><td></td><b>TOTAL TTC des factures:</b></td><td style="text-align: right"><b>' . BimpTools::displayMoneyValue($total_facture, 'EUR', 0, 0, 1, 2, 0, ',', 1) . '</b></td></tr>';

        $html .= '</table>';
        $html .= '</div>';

        $html .= '</div>';

        $this->writeContent($html);
    }
}
