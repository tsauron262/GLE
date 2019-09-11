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
        $this->typeObject = "invoice";
    }

    protected function initData()
    {
        if (isset($this->object) && is_a($this->object, 'Facture')) {
            if (isset($this->object->id) && $this->object->id) {
                $this->bimpCommObject = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', (int) $this->object->id);
                $this->facture = $this->object;
                $this->facture->fetch_thirdparty();

                global $user;
                
                $this->pdf->addCgvPages = false;

                $this->pdf->SetTitle($this->langs->convToOutputCharset($this->object->ref));
                $this->pdf->SetSubject($this->langs->transnoentities("Invoice"));
                $this->pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $this->pdf->SetAuthor($this->langs->convToOutputCharset($user->getFullName($this->langs)));
                $this->pdf->SetKeyWords($this->langs->convToOutputCharset($this->object->ref) . " " . $this->langs->transnoentities("Invoice") . " " . $this->langs->convToOutputCharset($this->object->thirdparty->name));

                $contacts = $this->facture->getIdContact('external', 'BILLING');
                if (isset($contacts[0]) && $contacts[0]) {
                    BimpTools::loadDolClass('contact');
                    $contact = new Contact($this->db);
                    if ($contact->fetch((int) $contacts[0]) > 0) {
                        $this->contact = $contact;
                    }
                }
                
                $contacts = $this->facture->getIdContact('external', 'CLIFINAL');
                if (isset($contacts[0]) && $contacts[0]) {
                    BimpTools::loadDolClass('contact');
                    $contact = new Contact($this->db);
                    if ($contact->fetch((int) $contacts[0]) > 0) {
                        $this->contactFinal = $contact;
                    }
                }
            } else {
                $this->errors[] = 'Facture invalide (ID absent)';
            }
        } else {
            $this->errors[] = 'Aucune facture spécifiée';
        }

        parent::initData();
    }

    protected function initHeader()
    {
        parent::initHeader();

        // Titre facture:
        $docName = '';
        switch ($this->facture->type) {
            case 1: $docName = $this->langs->transnoentities('InvoiceReplacement');
                break;
            case 2: $docName = $this->langs->transnoentities('InvoiceAvoir');
                break;
            case 3: $docName = $this->langs->transnoentities('InvoiceDeposit');
                break;
            case 4: $docName = $this->langs->transnoentities('InvoiceProFormat');
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

        $this->header_vars['doc_name'] = $docName;
        $this->header_vars['doc_ref'] = $docRef;
    }
    
    public function getDocInfosHtml()
    {
        global $db, $conf;

        if (isset($this->facture->thirdparty) && !is_null($this->facture->thirdparty)) {
            $soc = $this->facture->thirdparty;
        } elseif (isset($this->facture->socid) && $this->facture->socid) {
            $soc = new Societe($db);
            $soc->fetch($this->facture->socid);
        } else {
            $soc = null;
        }
        
        $html .= '<div>';
        
        // Ref. client:
        if ($this->facture->ref_client) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('RefCustomer') . ' : </span>' . $this->langs->convToOutputCharset($this->facture->ref_client) . '<br/>';
        }

        // Ref facture de remplacement: 
        $objectidnext = $this->facture->getIdReplacingInvoice('validated');
        if ($this->facture->type == 0 && $objectidnext) {
            $factureReplacing = new Facture($db);
            $factureReplacing->fetch($objectidnext);
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('ReplacementByInvoice') . ' : </span>' . $this->langs->convToOutputCharset($factureReplacing->ref) . '<br/>';
        }

        // Ref facture remplacée
        if ($this->facture->type == 1) {
            $factureReplaced = new Facture($db);
            $factureReplaced->fetch($this->facture->fk_facture_source);
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('ReplacementInvoice') . ' : </span>' . $this->langs->convToOutputCharset($factureReplaced->ref) . '<br/>';
        }

        if ($this->facture->type == 2 && !empty($this->facture->fk_facture_source)) {
            $factureReplaced = new Facture($db);
            $factureReplaced->fetch($this->facture->fk_facture_source);
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('CorrectionInvoice') . ' : </span>' . $this->langs->convToOutputCharset($factureReplaced->ref) . '<br/>';
        }

        // Dates: 
        $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('DateInvoice') . ' : </span>' . dol_print_date($this->facture->date, "day", false, $this->langs) . '<br/>';

        if (!empty($conf->global->INVOICE_POINTOFTAX_DATE)) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('DatePointOfTax') . ' : </span>' . dol_print_date($this->facture->date_pointoftax, "day", false, $this->langs) . '<br/>';
        }

        if ($this->facture->type != 2) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('DateDue') . ' : </span>' . dol_print_date($this->facture->date_lim_reglement, "day", false, $this->langs) . '<br/>';
        }

        // Code client: 
        if (isset($soc->code_client)) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('CustomerCode') . ' : </span>' . $this->langs->transnoentities($soc->code_client) . '<br/>';
        }

        if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
            $contacts = $this->facture->getIdContact('internal', 'SALESREPFOLL');
            if (count($contacts)) {
                $usertmp = new User($db);
                $usertmp->fetch($contacts[0]);
                $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('SalesRepresentative') . ' : </span>' . $usertmp->getFullName($this->langs) . '<br/>';
            }
        }

        $linkedObjects = pdf_getLinkedObjects($this->facture, $this->langs);

        if (!empty($linkedObjects)) {
            foreach ($linkedObjects as $lo) {
                if (static::$type === 'sav') {
                    $html .= '<span style="font-weight: bold;">' . $lo['ref_title'] . ' : </span>';
                    $html .= $lo['ref_value'];
                    if (!empty($lo['date_value'])) {
                        $html .= ' / ' . $lo['date_value'];
                    }
                    $html .= '<br/>';
                } else {
                    $refObject = '<span style="font-weight: bold">' . $lo['ref_title'] . ' : </span>' . $lo['ref_value'];
                    if (!empty($lo['date_value'])) {
                        $refObject .= ' / ' . $lo['date_value'];
                    }
                    $html .= $refObject . '<br/>';
                }
            }
        }
        
        $html .= '</div>';
        
        $html .= parent::getDocInfosHtml();
        
        return $html;
    }

    public function getPaymentInfosHtml()
    {
        if ($this->facture->statut === Facture::STATUS_CLOSED || $this->facture->paye) {
            return '';
        }

        global $conf;

        $html = '<div style="font-size: 7px; line-height: 8px;">';
        $html .= '<table style="width: 100%" cellpadding="5">';

        // Conditions de paiement: 
        if ($this->facture->type != 2) {
            if ($this->facture->cond_reglement_code || $this->facture->cond_reglement) {
                $html .= '<tr><td>';
                $html .= '<strong>' . $this->langs->transnoentities("PaymentConditions") . ': </strong><br/>';
                $label = $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) != ('PaymentCondition' . $this->object->cond_reglement_code) ? $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) : $this->langs->convToOutputCharset($this->object->cond_reglement_doc);
                $label = str_replace('\n', "\n", $label);
                $html .= $label;
                $html .= '</td></tr>';
            }

            $error = '';
            if (empty($this->object->mode_reglement_code) && empty($conf->global->FACTURE_CHQ_NUMBER) && empty($conf->global->FACTURE_RIB_NUMBER)) {
                $error = $this->langs->transnoentities("ErrorNoPaiementModeConfigured");
            } elseif (($this->object->mode_reglement_code == 'CHQ' && empty($conf->global->FACTURE_CHQ_NUMBER) && empty($this->object->fk_account) && empty($this->object->fk_bank)) || ($this->object->mode_reglement_code == 'VIR' && empty($conf->global->FACTURE_RIB_NUMBER) && empty($this->object->fk_account) && empty($this->object->fk_bank))) {
                $error = $this->langs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup", $object->mode_reglement_code);
            }

            if ($error) {
                $html .= '<tr><td>';
                $html .= '<p style="text-color: #C80000; font-weight: bold;">' . $error . '</p>';
                $html .= '</td></tr>';
            }
        }

        // Mode de paiement: 
        if ($this->object->mode_reglement_code && $this->object->mode_reglement_code != 'CHQ' && $this->object->mode_reglement_code != 'VIR') {
            $html .= '<tr><td>';
            $html .= '<strong>' . $this->langs->transnoentities("PaymentMode") . '</strong>:<br/>';
            $html .= $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) != ('PaymentType' . $this->object->mode_reglement_code) ? $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) : $this->langs->convToOutputCharset($this->object->mode_reglement);
            $html .= '</td></tr>';
        }

        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'CHQ') {

            if (!empty($conf->global->FACTURE_CHQ_NUMBER)) {
                if ($conf->global->FACTURE_CHQ_NUMBER > 0) {
                    $html .= '<tr><td>';
                    if (!class_exists('Account')) {
                        require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                    }
                    $account = new Account($this->db);
                    $account->fetch($conf->global->FACTURE_CHQ_NUMBER);

                    $html .= '<span style="font-style: italic">' . $this->langs->transnoentities('PaymentByChequeOrderedTo', $account->proprio) . ':</span><br/><br/>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $html .= '<strong>' . str_replace("\n", '<br/>', $this->langs->convToOutputCharset($account->owner_address)) . '</strong>';
                    }
                    $html .= '</td></tr>';
                } elseif ($conf->global->FACTURE_CHQ_NUMBER == -1) {
                    $html .= '<tr><td>';
                    $html .= $this->langs->transnoentities('PaymentByChequeOrderedTo', $this->fromCompany->name) . '<br/>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $html .= $this->langs->convToOutputCharset($this->fromCompany->getFullAddress()) . '<br/>';
                    }
                    $html .= '</td></tr>';
                }
            }
        }

//        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'VIR') {
            if (!empty($this->object->fk_account) || !empty($this->object->fk_bank) || !empty($conf->global->FACTURE_RIB_NUMBER)) {
                $html .= '<tr><td>';
                $bankid = (empty($this->object->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $this->object->fk_account);
                if (!empty($this->object->fk_bank)) {
                    $bankid = $this->object->fk_bank;
                }

                require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
                $account = new Account($this->db);
                $account->fetch($bankid);
                $html .= $this->getBankHtml($account);
                $html .= '</td></tr>';
            }
//        }

        $html .= '</table></div>';

        return $html;
    }

    public function getPaymentsHtml()
    {
        $html = '';
        global $conf;

        $sign = 1;
        if ($this->object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) {
            $sign = -1;
        }

        $bdb = new BimpDb($this->db);

        $sql = "SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva,  re.amount_ttc, re.multicurrency_amount_ttc,";
        $sql.= " re.description, re.fk_facture_source,";
        $sql.= " f.type, f.datef";
        $sql.= " FROM " . MAIN_DB_PREFIX . "societe_remise_except as re, " . MAIN_DB_PREFIX . "facture as f";
        $sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = " . $this->object->id;

        $remises = $bdb->executeS($sql);

        $sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
        $sql.= " cp.code";
        $sql.= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf, " . MAIN_DB_PREFIX . "paiement as p";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "c_paiement as cp ON p.fk_paiement = cp.id";
        $sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = " . $this->object->id;
        $sql.= " ORDER BY p.datep";

        $payments = $bdb->executeS($sql);

        if ((is_null($remises) || !count($remises)) && (is_null($payments) || !count($payments))) {
            return '';
        }

//        $html = '<div>';
        $html .= '<br/>';
        $html .= '<table style="width: 100%" cellpadding="3">';

        $html .= '<tr>';
        $html .= '<td colspan="4" style="font-weight: bold; font-size: 8px">';
        if ($this->object->type == 2) {
            $html .= $this->langs->transnoentities("PaymentsBackAlreadyDone");
        } else {
            $html .= $this->langs->transnoentities("PaymentsAlreadyDone");
        }
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td style="background-color: #DCDCDC; font-size: 7px">';
        $html .= $this->langs->transnoentities("Payment");
        $html .= '</td>';
        $html .= '<td style="background-color: #DCDCDC; font-size: 7px">';
        $html .= $this->langs->transnoentities("Amount");
        $html .= '</td>';
        $html .= '<td style="background-color: #DCDCDC; font-size: 7px">';
        $html .= $this->langs->transnoentities("Type");
        $html .= '</td>';
        $html .= '<td style="background-color: #DCDCDC; font-size: 7px">';
        $html .= $this->langs->transnoentities("Num");
        $html .= '</td>';
        $html .= '</tr>';

        if (is_null($remises)) {
            $html .= '<tr><td colspan="4">';
            $html .= '<p style="font-weight: bold; font-size: 7px; color: #C80000">' . $this->db->lasterror() . '</p>';
            $html .= '</td></tr>';
        } else if (count($remises)) {
            $invoice = new Facture($this->db);
            foreach ($remises as $obj) {
                if ($obj->type == 2)
                    $text = $this->langs->trans("CreditNote");
                elseif ($obj->type == 3)
                    $text = $this->langs->trans("Deposit");
                else
                    $text = $this->langs->trans("UnknownType");

                $invoice->fetch($obj->fk_facture_source);
            }

            $html .= '<tr>';
            $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
            $html .= dol_print_date($obj->datef, 'day', false, $this->langs, true);
            $html .= '</td>';
            $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
            $html .= price(($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? $obj->multicurrency_amount_ttc : $obj->amount_ttc, 0, $this->langs);
            $html .= '</td>';
            $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
            $html .= $text;
            $html .= '</td>';
            $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
            $html .= $invoice->ref;
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Loop on each payment
        if (is_null($payments)) {
            $html .= '<tr><td colspan="4">';
            $html .= '<p style="font-weight: bold; font-size: 7px; color: #C80000">' . $this->db->lasterror() . '</p>';
            $html .= '</td></tr>';
        } elseif (count($payments)) {
            foreach ($payments as $row) {
                $html .= '<tr>';
                $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
                $html .= dol_print_date($this->db->jdate($row->date), 'day', false, $this->langs, true);
                $html .= '</td>';
                $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
                $html .= price($sign * (($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? $row->multicurrency_amount : $row->amount), 0, $this->langs);
                $html .= '</td>';
                $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
                $html .= $this->langs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);
                $html .= '</td>';
                $html .= '<td style="font-size: 7px; border-bottom: solid 1px #DCDCDC;">';
                $html .= $row->num;
                $html .= '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';
//        $html .= '</div>';
        $html .= '<br/><br/><br/>';

        return $html;
    }

    public function getAfterTotauxHtml()
    {
        return '';
    }
}
