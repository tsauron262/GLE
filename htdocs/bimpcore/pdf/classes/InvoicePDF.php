<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

class InvoicePDF extends BimpModelPDF
{

    public static $type = 'invoice';
    public $facture;

    public function __construct($id_facture)
    {
        parent::__construct();

        global $db;
        $this->facture = new Facture($db);
        $this->facture->fetch($id_facture);

        $this->langs->load("bills");
        $this->langs->load("products");
    }

    protected function initHeader()
    {
        parent::initHeader();

        global $db, $conf;

        if (isset($this->facture->thirdparty) && !is_null($this->facture->thirdparty)) {
            $soc = $this->facture->thirdparty;
        } elseif (isset($this->facture->socid) && $this->facture->socid) {
            $soc = new Societe($db);
            $soc->fetch($this->facture->socid);
        } else {
            $soc = null;
        }

        // Titre facture:
        $docName = '';
        switch ($this->facture->type) {
            case 1: $docName = $this->langs->transnoentities('InvoiceReplacement');
                break;
            case 2: $docName = $this->langs->transnoentities('InvoiceAvoir');
                break;
            case 3: $docName = $this->langs->transnoentities('InvoiceDeposit');
                break;
            case 3: $docName = $this->langs->transnoentities('InvoiceProFormat');
                break;
            default:
                $docName = $this->langs->transnoentities('Invoice');
        }

        if ($this->sitationinvoice) {
            $docName = $this->langs->transnoentities('InvoiceSituation');
        }

        // Réf facture: 
        $docRef = $this->langs->transnoentities("Ref") . " : " . $this->langs->convToOutputCharset($this->facture->ref);
        if ($this->facture->statut == Facture::STATUS_DRAFT) {
            $docRef = '<span style="color: #800000"> ' . $docRef . ' - ' . $this->langs->transnoentities("NotValidated") . '</span>';
        }

        $rows = '';
        $nRows = 0;

        // Ref. client:
        if ($this->facture->ref_client) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('RefCustomer') . ' : ' . $this->langs->convToOutputCharset($this->facture->ref_client) . '</div>';
            $nRows++;
        }

        // Ref facture de remplacement: 
        $objectidnext = $this->facture->getIdReplacingInvoice('validated');
        if ($this->facture->type == 0 && $objectidnext) {
            $factureReplacing = new Facture($db);
            $factureReplacing->fetch($objectidnext);
            $rows .= '<div class="row">' . $this->langs->transnoentities('ReplacementByInvoice') . ' : ' . $this->langs->convToOutputCharset($factureReplacing->ref) . '</div>';
            $nRows++;
        }

        // Ref facture remplacée
        if ($this->facture->type == 1) {
            $factureReplaced = new Facture($db);
            $factureReplaced->fetch($this->facture->fk_facture_source);
            $rows .= '<div class="row">' . $this->langs->transnoentities('ReplacementInvoice') . ' : ' . $this->langs->convToOutputCharset($factureReplaced->ref) . '</div>';
            $nRows++;
        }

        if ($this->facture->type == 2 && !empty($this->facture->fk_facture_source)) {
            $factureReplaced = new Facture($db);
            $factureReplaced->fetch($this->facture->fk_facture_source);
            $rows .= '<div class="row">' . $this->langs->transnoentities('CorrectionInvoice') . ' : ' . $this->langs->convToOutputCharset($factureReplaced->ref) . '</div>';
            $nRows++;
        }

        // Dates: 
        $rows .= '<div class="row">' . $this->langs->transnoentities('DateInvoice') . ' : ' . dol_print_date($this->facture->date, "day", false, $this->langs) . '</div>';
        $nRows++;


        if (!empty($conf->global->INVOICE_POINTOFTAX_DATE)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('DatePointOfTax') . ' : ' . dol_print_date($this->facture->date_pointoftax, "day", false, $this->langs) . '</div>';
            $nRows++;
        }

        if ($this->facture->type != 2) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('DateDue') . ' : ' . dol_print_date($this->facture->date_lim_reglement, "day", false, $this->langs) . '</div>';
            $nRows++;
        }

        // Code client: 
        if (isset($soc->code_client)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('CustomerCode') . ' : ' . $this->langs->transnoentities($soc->code_client) . '</div>';
            $nRows++;
        }

        if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
            $contacts = $this->facture->getIdContact('internal', 'SALESREPFOLL');
            if (count($contacts)) {
                $usertmp = new User($db);
                $usertmp->fetch($contacts[0]);
                $rows .= '<div class="row">' . $this->langs->transnoentities('CustomerCode') . ' : ' . $usertmp->getFullName($this->langs) . '</div>';
                $nRows++;
            }
        }

        $linkedObjects = pdf_getLinkedObjects($this->facture, $this->langs);

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

        $this->header_vars['header_right'] = $this->renderTemplate(self::$tpl_dir . '/' . static::$type . '/header_right.html', array(
            'doc_name' => $docName,
            'doc_ref'  => $docRef,
            'rows'     => $rows
        ));
    }

    protected function renderContent()
    {
        $contacts = $this->facture->getIdContact('external', 'BILLING');

        if (count($contacts)) {
            $this->facture->fetch_contact($contacts[0]);
            $contact = $this->facture->contact;
            $thirdparty = $contact;
        } else {
            if (is_null($this->facture->thirdparty)) {
                $this->facture->fetch_thirdparty();
            }
            $contact = null;
            $thirdparty = $this->facture->thirdparty;
        }
        
        $this->writeContent($this->renderAddresses($thirdparty, $contact));

        $table = new BimpPDF_Table($this->pdf);
        $table->addCol('col1', 'Colonne 1', 80);
        $table->addCol('col2', 'Colonne 2', 20);
        $table->addCol('col3', 'Colonne 3', 50);
        $table->addCol('col4', 'Colonne 4 BLUE', 0, 'color: #0000FF');

        $table->rows = array(
            array(
                'col1' => 'CONTENT' . "<br/>" . 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => array(
                    'content' => 'COLOR RED',
                    'style' => 'color: #FF0000'
                ),
                'col3' => array(
                    'content' => 'COLOR WHITE',
                    'style' => 'color: #FFFFFF; background-color: #333333'
                ),
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => array(
                    'content' => 'COLSPAN 2',
                    'colspan' => 2
                ),
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => array(
                    'content' => 'COLSPAN 3',
                    'colspan' => 3
                ),
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => array(
                    'content' => 'COLSPAN 4',
                    'colspan' => 4
                )
            ),
            array(
                'col1' => array(
                    'content' => 'COLSPAN 5',
                    'colspan' => 5
                )
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => array(
                    'content' => 'COLSPAN 4 COLOR',
                    'colspan' => 4,
                    'style' => 'color: red' 
                )
            ),
            array(
                'col1' => array(
                    'content' => 'COLSPAN 2',
                    'colspan' => 2
                ),
                'col3' => array(
                    'content' => 'COLSPAN 2',
                    'colspan' => 2
                ),
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
            array(
                'col1' => 'CONTENT',
                'col2' => 'CONTENT',
                'col3' => 'CONTENT',
                'col4' => 'CONTENT'
            ),
        );

        $table->write();
    }
}
