<?php

require_once __DIR__ . '/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

class InvoicePDF extends BimpDocumentPDF
{

    public static $type = 'invoice';
    public $facture = null;

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("bills");
        $this->langs->load("products");
        $this->facture = new Facture($db);
    }

    protected function initData()
    {
        if (isset($this->object) && is_a($this->object, 'Facture')) {
            if (isset($this->object->id) && $this->object->id) {
                $this->facture = $this->object;
                $this->facture->fetch_thirdparty();

                global $user;
                
                $this->pdf->SetTitle($this->langs->convToOutputCharset($this->object->ref));
                $this->pdf->SetSubject($this->langs->transnoentities("Invoice"));
                $this->pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $this->pdf->SetAuthor($this->langs->convToOutputCharset($user->getFullName($this->langs)));
                $this->pdf->SetKeyWords($this->langs->convToOutputCharset($this->object->ref) . " " . $this->langs->transnoentities("Invoice") . " " . $this->langs->convToOutputCharset($this->object->thirdparty->name));
            } else {
                $this->errors[] = 'Facture invalide (ID absent)';
            }
        } else {
            $this->errors[] = 'Aucune facture spécifiée';
        }
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
                $rows .= '<div class="row">' . $this->langs->transnoentities('SalesRepresentative') . ' : ' . $usertmp->getFullName($this->langs) . '</div>';
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

        $this->header_vars['header_right'] = $this->renderTemplate(self::$tpl_dir . 'header_right.html', array(
            'doc_name' => $docName,
            'doc_ref'  => $docRef,
            'rows'     => $rows
        ));
    }
}
