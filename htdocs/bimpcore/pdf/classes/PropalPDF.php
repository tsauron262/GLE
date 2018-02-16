<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

class PropalPDF extends BimpModelPDF
{

    public static $type = 'propal';
    public $propal = null;
    public $mode = "normal";

    public function __construct($id_propal)
    {
        parent::__construct();

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        global $db;

        $this->propal = new Propal($db);
        if ($this->propal->fetch($id_propal) <= 0) {
            $this->errors[] = 'ID propal invalide: ' . $id_propal;
        } else {
            if ($this->propal->fetch_thirdparty($this->propal->socid) <= 0) {
                $this->errors[] = 'Tiers invalide pour cette proposition commerciale';
            }

            if ($this->propal->fetch_contact($this->propal->contactid)) {
                $this->errors[] = 'Contact invalide pour cette proposition commerciale';
            }
        }
    }

    public function initData()
    {
//        $this->typeObject = "propal";
//        $this->prefName = "loyer_";
//
//        $titre = "";
//
//        $arrayHead = array("desc" => "Description", "prix" => array("Prix", "€"), "qty" => "Quantité", "remise" => array("Remise", " %"), "total_ht" => array("Total Ht", "€"), "tva" => array("Tva", " %"), "total_ttc" => array("Total Ttc", "€"));
//        $arrayData = array();
//        $arrayTot = array();
//
//        $this->object->fetch_lines();
//        foreach ($this->object->lines as $line) {
//            $arrayData[] = array("desc"      => $line->desc,
//                "prix"      => $line->subprice,
//                "qty"       => $line->qty,
//                "remise"    => $line->remise_percent,
//                "total_ht"  => $line->total_ht,
//                "tva"       => $line->tva_tx,
//                "total_ttc" => $line->total_ttc);
//
//            $arrayTot["prix"] += $line->subprice;
//        }
//        $tabHtml = $this->renderTable($arrayHead, $arrayData);
//
//        if (isset($this->object) && is_object($this->object)) {
//            $titre .= "<h2>" . get_class($this->object) . " " . $this->object->ref . "</h2>";
//        }
//
//        $this->text .= $this->pdf->renderTemplate($this->tpl_dir . '/table.html', array("titre" => $titre, "table" => $tabHtml));
//
//        //ci apres juste pour les test
//        if ($this->mode == "loyer")
//            $this->text .= "<h3>En mode Loyer</h3>";
//
//        $espace = "";
//        $this->text .= "<br/><br/><br/>";
//        for ($i = 0; $i < 100; $i++) {
//            $espace .= " - ";
//            $this->text .= "<br/>" . $espace . "Ligne n°" . $i;
//        }
    }

    protected function initHeader()
    {
        parent::initHeader();

        global $conf, $db;

        $docName = $this->langs->transnoentities('CommercialProposal');
        $docRef = $this->langs->transnoentities("Ref") . " : " . $this->langs->convToOutputCharset($this->propal->ref);

        $rows = '';
        $nRows = 0;

        // Réf client: 
        if ($this->propal->ref_client) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('RefCustomer') . ' : ' . $this->langs->convToOutputCharset($this->propal->ref_client) . '</div>';
            $nRows++;
        }

        // Dates: 
        if (!empty($this->propal->date)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('Date') . ' : ' . dol_print_date($this->propal->date, "day", false, $this->langs) . '</div>';
            $nRows++;
        }

        if (!empty($this->propal->fin_validite)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('DateEndPropal') . ' : ' . dol_print_date($this->propal->fin_validite, "day", false, $this->langs, true) . '</div>';
            $nRows++;
        }

        // Code client: 
        if (isset($this->propal->thirdparty->code_client)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('CustomerCode') . ' : ' . $this->langs->transnoentities($this->propal->thirdparty->code_client) . '</div>';
            $nRows++;
        }

        // Commercial: 
        if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
            $contacts = $this->propal->getIdContact('internal', 'SALESREPFOLL');
            if (count($contacts)) {
                $usertmp = new User($db);
                $usertmp->fetch($contacts[0]);
                $rows .= '<div class="row">' . $this->langs->transnoentities('SalesRepresentative') . ' : ' . $usertmp->getFullName($this->langs) . '</div>';
                $nRows++;
            }
        }

        // Objets liés:
        $linkedObjects = pdf_getLinkedObjects($this->propal, $this->langs);

        if (!empty($linkedObjects)) {
            foreach ($linkedObjects as $lo) {
                $refObject = $lo['ref_title'] . ' : ' . $lo['ref_value'];
                if (!empty($lo['date_value'])) {
                    $refObject .= ' / ' . $lo['date_value'];
                }

                $rows .= '<div class="row">' . $refObject . '</div>';
                $nRows++;
            }
        }

        $this->pdf->topMargin = 40;

        if ($nRows > 2) {
            $this->pdf->topMargin += 4 * ($nRows - 2);
        }

        $this->header_vars['header_right'] = $this->renderTemplate(self::$tpl_dir . 'header_right.html', array(
            'doc_name' => $docName,
            'doc_ref'  => $docRef,
            'rows'     => $rows
        ));
    }

    protected function renderContent()
    {
        global $conf;

        $this->writeContent($this->renderAddresses($this->propal->thirdparty, $this->propal->contact));

        $table = new BimpPDF_AmountsTable($this->pdf);

        $lines = $this->propal->lines;
        $i = 0;
        foreach ($lines as $line) {
            $row = array(
                'desc'      => $line->desc,
                'total_ht'  => BimpTools::displayMoneyValue($line->total_ht, ''),
                'total_ttc' => BimpTools::displayMoneyValue($line->total_ttc, '')
            );

            $row['pu_ht'] = pdf_getlineupexcltax($this->propal, $i, $this->langs);
            $row['qte'] = pdf_getlineqty($this->propal, $i, $this->langs);

            if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
                $row['tva'] = pdf_getlinevatrate($this->propal, $i, $this->langs);
            }

            if ($conf->global->PRODUCT_USE_UNITS) {
                $row['unite'] = pdf_getlineunit($this->propal, $i, $this->langs);
            }

            if ($line->remise_percent) {
                $row['reduc'] = pdf_getlineremisepercent($this->propal, $i, $this->langs);
            }

            $row['total_ht'] = pdf_getlinetotalexcltax($this->propal, $i, $this->langs);

            if ($conf->multicurrency->enabled && $this->propal->multicurrency_tx != 1)
                $tva_line = $line->multicurrency_total_tva;
            else
                $tva_line = $line->total_tva;
            
            $table->rows[] = $row;
            $i++;
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
    }
}
