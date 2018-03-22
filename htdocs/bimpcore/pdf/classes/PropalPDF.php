<?php

require_once __DIR__ . '/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

ini_set('display_errors', 1);

class PropalPDF extends BimpDocumentPDF
{

    public static $type = 'propal';
    public $propal = null;
    public $mode = "normal";

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        $this->typeObject = 'propal';

        $this->propal = new Propal($db);
    }

    protected function initData()
    {
        if (isset($this->object) && is_a($this->object, 'Propal')) {
            if (isset($this->object->id) && $this->object->id) {
                $this->propal = $this->object;
                if (isset($this->propal->socid) && $this->propal->socid) {
                    if (!isset($this->propal->thirdparty)) {
                        $this->propal->fetch_thirdparty();
                    }
                }

                global $user;

                $this->pdf->SetTitle($this->langs->convToOutputCharset($this->object->ref));
                $this->pdf->SetSubject($this->langs->transnoentities("CommercialProposal"));
                $this->pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $this->pdf->SetAuthor($this->langs->convToOutputCharset($user->getFullName($this->langs)));
                $this->pdf->SetKeyWords($this->langs->convToOutputCharset($this->object->ref) . " " . $this->langs->transnoentities("CommercialProposal") . " " . $this->langs->convToOutputCharset($this->object->thirdparty->name));

                if (is_null($this->propal->thirdparty) || !isset($this->propal->thirdparty->id) || !$this->propal->thirdparty->id) {
                    $this->errors[] = 'Aucun client renseigné pour cette proposition commerciale';
                }
            } else {
                $this->errors[] = 'Proposition commerciale invalide (ID absent)';
            }
        } else {
            $this->errors[] = 'Aucune proposition commerciale spécifiée';
        }

        parent::initData();
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

    public function getPaymentInfosHtml()
    {
        global $conf;

        $html = '<div style="font-size: 7px; line-height: 8px;">';
        $html .= '<table style="width: 100%" cellpadding="5">';

        // Date de livraison
        if (!empty($this->object->date_livraison)) {
            $html .= '<tr><td>';
            $html .= dol_print_date($this->object->date_livraison, "daytext", false, $this->langs, true) . '<br/>';
            $html .= '</td></tr>';
        } elseif ($this->object->availability_code || (isset($this->object->availability) && $this->object->availability)) {
            $html .= '<tr><td>';
            $html .= '<strong>' . $this->langs->transnoentities("AvailabilityPeriod") . ': </strong>';
            $label = $this->langs->transnoentities("AvailabilityType" . $this->object->availability_code) != ('AvailabilityType' . $this->object->availability_code) ? $this->langs->transnoentities("AvailabilityType" . $this->object->availability_code) : $this->langs->convToOutputCharset($this->object->availability);
            $label = str_replace('\n', "\n", $label);
            $html .= $label;
            $html .= '</td></tr>';
        }

        // Conditions de paiement: 
        if (empty($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND) && ($this->object->cond_reglement_code || $this->object->cond_reglement)) {
            $html .= '<tr><td>';
            $html .= '<strong>' . $this->langs->transnoentities("PaymentConditions") . ': </strong><br/>';
            $label = $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) != ('PaymentCondition' . $this->object->cond_reglement_code) ? $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) : $this->langs->convToOutputCharset($this->object->cond_reglement_doc);
            $label = str_replace('\n', "\n", $label);
            $html .= $label;
            $html .= '</td></tr>';
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

        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'VIR') {
            if (!empty($this->object->fk_account) || !empty($this->object->fk_bank) || !empty($conf->global->FACTURE_RIB_NUMBER)) {
                $html .= '<tr><td>';
                $bankid = (empty($this->object->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $this->object->fk_account);
                if (!empty($this->object->fk_bank)) {
                    $bankid = $this->object->fk_bank;
                }

                $account = new Account($this->db);
                $account->fetch($bankid);
                $html .= $this->getBankHtml($account);
                $html .= '</td></tr>';
            }
        }

        $html .= '</table></div>';

        return $html;
    }
}
